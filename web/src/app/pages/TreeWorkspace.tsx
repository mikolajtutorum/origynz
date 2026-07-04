import { useMemo, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { useTreeGraph, useUpdateTree } from '@core/queries/trees';
import {
  useAddRelative,
  useCreateRelationship,
  useRemovePerson,
  useUpdatePerson,
} from '@core/queries/people';
import { useUploadMedia } from '@core/queries/media';
import { buildFamilyGraph, focusFamily } from '@core/tree/graph';
import type { PersonFormValues } from '@core/validation/person';
import type { RelationRole } from '@core/api/endpoints/people';
import type { Person, RelationshipType } from '@core/models';
import { getBlob } from '@core/api/client';
import { gedcomApi } from '@core/api/endpoints/gedcom';
import { AppLayout } from '../components/AppLayout';
import { Button, FormError, Modal, Select } from '../components/ui';
import { PersonSearchInput } from '../components/PersonSearchInput';
import { downloadBlob } from '../lib/download';
import { TreeCanvas } from '../components/tree/TreeCanvas';
import { PersonPanel } from '../components/tree/PersonPanel';
import { PersonForm } from '../components/tree/PersonForm';
import { AddRelativeOverlay } from '../components/tree/AddRelativeOverlay';
import { applyApiErrors } from '../lib/applyApiErrors';

// Sensible defaults for the add-person form, derived from the chosen relation.
const ROLE_LABEL: Record<RelationRole, string> = {
  father: 'father', mother: 'mother', parent: 'parent',
  son: 'son', daughter: 'daughter', child: 'child',
  brother: 'brother', sister: 'sister',
  'half-brother': 'half-brother', 'half-sister': 'half-sister',
  partner: 'partner', spouse: 'spouse',
};
const MALE_ROLES: RelationRole[] = ['father', 'son', 'brother', 'half-brother'];
const FEMALE_ROLES: RelationRole[] = ['mother', 'daughter', 'sister', 'half-sister'];
// Partners rarely share the anchor's surname; everyone else usually does.
const KEEP_SURNAME_ROLES: RelationRole[] = [
  'father', 'mother', 'parent', 'son', 'daughter', 'child', 'brother', 'sister', 'half-brother', 'half-sister',
];

function relativeDefaults(role: RelationRole, anchor: Person): Partial<PersonFormValues> {
  return {
    sex: MALE_ROLES.includes(role) ? 'male' : FEMALE_ROLES.includes(role) ? 'female' : 'unknown',
    surname: KEEP_SURNAME_ROLES.includes(role) ? (anchor.surname ?? '') : '',
    is_living: true,
  };
}

function ConnectModal({ treeId, person, onClose }: { treeId: string; person: Person; onClose: () => void }) {
  const create = useCreateRelationship(treeId);
  const [other, setOther] = useState<Person | null>(null);
  const [type, setType] = useState<RelationshipType>('parent');
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    if (!other) return;
    setError(null);
    try {
      // person is the parent for 'parent', the child's relationship handled by API symmetry.
      await create.mutateAsync({ person_id: person.id, related_person_id: other.id, type });
      onClose();
    } catch (e) {
      setError((e as Error).message);
    }
  };

  return (
    <Modal title="Connect to existing person" onClose={onClose}>
      <div className="flex flex-col gap-3">
        <FormError message={error} />
        <p className="text-sm text-ink-soft">
          Link <span className="font-medium text-ink">{person.display_name}</span> to another person already in this tree.
        </p>
        <Select label="Relationship" value={type} onChange={(e) => setType(e.target.value as RelationshipType)}>
          <option value="parent">Is a parent of…</option>
          <option value="child">Is a child of…</option>
          <option value="spouse">Is a spouse of…</option>
        </Select>
        <PersonSearchInput label="Person" selected={other} onSelect={setOther} treeId={treeId} excludeId={person.id} />
        <Button onClick={submit} loading={create.isPending} disabled={!other}>
          Connect
        </Button>
      </div>
    </Modal>
  );
}

function PhotoModal({ treeId, person, onClose }: { treeId: string; person: Person; onClose: () => void }) {
  const upload = useUploadMedia(treeId);
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    if (!file) return;
    setError(null);
    const form = new FormData();
    form.append('title', `${person.display_name} photo`);
    form.append('person_id', person.id);
    form.append('is_primary', '1');
    form.append('media_file', file);
    try {
      await upload.mutateAsync(form);
      onClose();
    } catch (e) {
      setError((e as Error).message);
    }
  };

  return (
    <Modal title={`Photo for ${person.display_name}`} onClose={onClose}>
      <div className="flex flex-col gap-3">
        <FormError message={error} />
        <input type="file" accept="image/*" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="text-sm" />
        <Button onClick={submit} loading={upload.isPending} disabled={!file}>
          Upload photo
        </Button>
      </div>
    </Modal>
  );
}

// Shown after an import when the owner couldn't be auto-detected: the user picks
// which person is the tree's home (the person the workspace centres on).
function HomePersonModal({
  treeId,
  onClose,
  onChosen,
}: {
  treeId: string;
  onClose: () => void;
  onChosen: (personId: string) => void;
}) {
  const update = useUpdateTree(treeId);
  const [person, setPerson] = useState<Person | null>(null);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    if (!person) return;
    setError(null);
    try {
      await update.mutateAsync({ owner_person_id: person.id });
      onChosen(person.id);
    } catch (e) {
      setError((e as Error).message);
    }
  };

  return (
    <Modal title="Which person is you?" onClose={onClose}>
      <div className="flex flex-col gap-3">
        <FormError message={error} />
        <p className="text-sm text-ink-soft">
          We couldn&apos;t tell which person in this file is you. Pick yourself (or whoever the tree should centre on) to
          set the home person.
        </p>
        <PersonSearchInput label="Home person" selected={person} onSelect={setPerson} treeId={treeId} />
        <Button onClick={submit} loading={update.isPending} disabled={!person}>
          Set home person
        </Button>
      </div>
    </Modal>
  );
}

export function TreeWorkspace() {
  const { id = '' } = useParams();
  const location = useLocation();
  const navigate = useNavigate();
  const { data, isLoading, isError, error } = useTreeGraph(id);

  const [focusId, setFocusId] = useState<string | null>(null);
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [modal, setModal] = useState<'edit' | 'connect' | 'photo' | null>(null);
  const [modalError, setModalError] = useState<string | null>(null);
  // Add-relative flow: pick a relative kind in the radial overlay (role: null),
  // then fill in the add-person form (role set).
  const [relativeFlow, setRelativeFlow] = useState<{ anchorId: string; role: RelationRole | null } | null>(null);
  // Bumped on every card tap so the mobile profile re-opens even when the
  // same person is tapped again. Must live above the early returns: hooks
  // may not render conditionally.
  const [selectTick, setSelectTick] = useState(0);
  // Set by the GEDCOM import flow when it couldn't auto-detect the home person.
  const [chooseHome, setChooseHome] = useState<boolean>(
    () => Boolean((location.state as { chooseHome?: boolean } | null)?.chooseHome),
  );

  const updatePerson = useUpdatePerson(id);
  const addRelative = useAddRelative(id);
  const removePerson = useRemovePerson(id);
  const updateTree = useUpdateTree(id);

  const graph = useMemo(
    () => (data ? buildFamilyGraph(data.people, data.relationships) : null),
    [data],
  );

  if (isLoading) {
    return (
      <AppLayout bleed>
        <div className="family-chart-board flex min-h-0 flex-1 items-center justify-center bg-[#fbf8f3]">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-700" />
        </div>
      </AppLayout>
    );
  }

  if (isError || !data || !graph) {
    return (
      <AppLayout bleed>
        <div className="flex min-h-0 flex-1 flex-col items-center justify-center gap-3 bg-[#fbf8f3]">
          <p className="text-red-600">Could not load tree: {(error as Error)?.message}</p>
          <Link to="/trees" className="text-blue-600 underline">
            Back to trees
          </Link>
        </div>
      </AppLayout>
    );
  }

  const effectiveFocus = focusId ?? data.owner_person_id ?? data.people[0]?.id ?? null;
  const effectiveSelected = selectedId ?? effectiveFocus;
  const selectedPerson = effectiveSelected ? graph.peopleById.get(effectiveSelected) : undefined;
  const family = effectiveSelected ? focusFamily(graph, effectiveSelected) : null;

  // The Parents button (or a panel link / find result) re-centres the tree on
  // the person and opens their panel.
  const reRoot = (personId: string) => {
    setFocusId(personId);
    setSelectedId(personId);
  };

  // Clicking a card only opens the person in the sidebar (full-screen profile
  // on mobile) — it never moves the tree.
  const select = (personId: string) => {
    setSelectedId(personId);
    setSelectTick((t) => t + 1);
  };

  // Clear the one-shot import flag so it doesn't reopen on back/refresh.
  const dismissChooseHome = () => {
    setChooseHome(false);
    navigate(location.pathname, { replace: true, state: null });
  };

  // Persist a new home person (the tree's owner_person_id) and re-centre on them.
  const setHomePerson = async (personId: string) => {
    try {
      await updateTree.mutateAsync({ owner_person_id: personId });
      reRoot(personId);
    } catch (e) {
      setModalError(applyApiErrors(e, () => {}));
    }
  };

  const saveEdit = async (values: PersonFormValues) => {
    if (!selectedPerson) return;
    setModalError(null);
    try {
      await updatePerson.mutateAsync({ id: selectedPerson.id, payload: values });
      setModal(null);
    } catch (e) {
      setModalError(applyApiErrors(e, () => {}));
    }
  };

  // Open the radial chooser for a person (from a card tab or the panel).
  const openAddRelative = (personId: string) => {
    setModalError(null);
    setSelectedId(personId);
    setRelativeFlow({ anchorId: personId, role: null });
  };

  const relativeAnchor = relativeFlow ? graph.peopleById.get(relativeFlow.anchorId) : undefined;

  const submitRelative = async (values: PersonFormValues) => {
    if (!relativeFlow?.role || !relativeFlow.anchorId) return;
    setModalError(null);
    try {
      await addRelative.mutateAsync({
        anchor_person_id: relativeFlow.anchorId,
        relation_role: relativeFlow.role,
        ...values,
      });
      setRelativeFlow(null);
    } catch (e) {
      setModalError(applyApiErrors(e, () => {}));
    }
  };

  const remove = async () => {
    if (!selectedPerson) return;
    if (!window.confirm(`Remove ${selectedPerson.display_name} from this tree?`)) return;
    await removePerson.mutateAsync(selectedPerson.id);
    setFocusId(data.owner_person_id ?? data.people[0]?.id ?? null);
    setSelectedId(null);
  };

  return (
    <AppLayout bleed>
      <div className="flex min-h-0 flex-1">
        {selectedPerson && family && (
          <PersonPanel
            person={selectedPerson}
            family={family}
            graph={graph}
            homePersonId={data.owner_person_id}
            openSignal={selectTick}
            isOwner={selectedPerson.id === data.owner_person_id}
            canManage={data.can_manage}
            showInteractions={data.tree.global_tree_enabled}
            onPick={reRoot}
            onEdit={() => {
              setModalError(null);
              setModal('edit');
            }}
            onAddRelative={() => openAddRelative(selectedPerson.id)}
            onEditPhoto={() => setModal('photo')}
            onConnect={() => setModal('connect')}
            onSetHome={() => setHomePerson(selectedPerson.id)}
            onDelete={remove}
          />
        )}

        <main className="flex min-h-0 min-w-0 flex-1 flex-col">
          {/* Compact on mobile (single MyHeritage-style row) to give the canvas
              as much vertical space as possible; roomier on desktop. */}
          <div className="flex items-center gap-1.5 border-b border-[#e2e2e2] bg-white px-2 py-1.5 lg:justify-between lg:gap-3 lg:px-8 lg:py-4">
            <Link
              to="/trees"
              className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[#68727b] hover:bg-[#f3f5f7] hover:text-[#2563eb] lg:hidden"
              aria-label="All trees"
            >
              <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <path d="M19 12H5m7-7-7 7 7 7" />
              </svg>
            </Link>
            <div className="min-w-0 flex-1 lg:flex-initial">
              <Link to="/trees" className="hidden text-[13px] text-[#68727b] hover:text-[#2563eb] lg:inline">
                ← All trees
              </Link>
              <h1 className="truncate text-[15px] font-semibold leading-tight text-[#1f252b] lg:text-[20px]">{data.tree.name}</h1>
              <p className="truncate text-[11px] leading-tight text-[#68727b] lg:text-[13px]">
                {data.people.length} people<span className="max-lg:hidden"> · your role: {data.access_level}</span>
              </p>
            </div>
            {data.can_manage && (
              <button
                onClick={async () => {
                  const blob = await getBlob(gedcomApi.exportPath(data.tree.id));
                  downloadBlob(blob, `${data.tree.name}.ged`);
                }}
                className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[#1f252b] hover:bg-[#f7f9fb] lg:h-auto lg:w-auto lg:gap-2 lg:rounded-md lg:border lg:border-[#d4dae1] lg:px-4 lg:py-2 lg:text-sm lg:font-medium"
                aria-label="Export GEDCOM"
              >
                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M12 3v12" />
                  <path d="m7 10 5 5 5-5" />
                  <path d="M5 21h14" />
                </svg>
                <span className="max-lg:hidden">Export GEDCOM</span>
              </button>
            )}
          </div>

          <div className="min-h-0 flex-1">
            {effectiveFocus ? (
              <TreeCanvas
                graph={graph}
                focusId={effectiveFocus}
                selectedId={effectiveSelected}
                onSelect={select}
                onFocus={reRoot}
                onAddRelative={openAddRelative}
              />
            ) : (
              <p className="rounded-xl border border-dashed border-neutral-300 p-10 text-center text-neutral-500">
                This tree has no people yet.
              </p>
            )}
          </div>
        </main>
      </div>

      {modal === 'edit' && selectedPerson && (
        <Modal title={`Edit ${selectedPerson.display_name}`} onClose={() => setModal(null)}>
          <PersonForm
            submitLabel="Save changes"
            submitting={updatePerson.isPending}
            error={modalError}
            defaultValues={{
              given_name: selectedPerson.given_name,
              middle_name: selectedPerson.middle_name ?? '',
              surname: selectedPerson.surname ?? '',
              sex: selectedPerson.sex,
              birth_date_text: selectedPerson.birth_date_text ?? '',
              birth_place: selectedPerson.birth_place ?? '',
              death_date_text: selectedPerson.death_date_text ?? '',
              death_place: selectedPerson.death_place ?? '',
              is_living: selectedPerson.is_living,
              headline: selectedPerson.headline ?? '',
              notes: selectedPerson.notes ?? '',
            }}
            onSubmit={saveEdit}
          />
        </Modal>
      )}

      {/* Step 1: pick which kind of relative to add. */}
      {relativeFlow && relativeFlow.role === null && relativeAnchor && (
        <AddRelativeOverlay
          anchor={relativeAnchor}
          onChoose={(role) => setRelativeFlow((f) => (f ? { ...f, role } : f))}
          onClose={() => setRelativeFlow(null)}
        />
      )}

      {/* Step 2: fill in the new person's details. */}
      {relativeFlow && relativeFlow.role && relativeAnchor && (
        <Modal
          title={`Add ${ROLE_LABEL[relativeFlow.role]} of ${relativeAnchor.display_name}`}
          onClose={() => setRelativeFlow(null)}
        >
          <PersonForm
            submitLabel="Add relative"
            submitting={addRelative.isPending}
            error={modalError}
            defaultValues={relativeDefaults(relativeFlow.role, relativeAnchor)}
            onSubmit={submitRelative}
          />
        </Modal>
      )}

      {modal === 'connect' && selectedPerson && (
        <ConnectModal treeId={id} person={selectedPerson} onClose={() => setModal(null)} />
      )}

      {modal === 'photo' && selectedPerson && (
        <PhotoModal treeId={id} person={selectedPerson} onClose={() => setModal(null)} />
      )}

      {chooseHome && data.can_manage && (
        <HomePersonModal
          treeId={id}
          onClose={dismissChooseHome}
          onChosen={(personId) => {
            reRoot(personId);
            dismissChooseHome();
          }}
        />
      )}
    </AppLayout>
  );
}
