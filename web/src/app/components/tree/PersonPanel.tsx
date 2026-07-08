import { useEffect, useMemo, useRef, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { peopleApi } from '@core/api/endpoints/people';
import { mediaApi } from '@core/api/endpoints/media';
import type { Person } from '@core/models';
import type { FamilyGraph, FocusFamily } from '@core/tree/graph';
import { useT } from '@core/i18n';
import { kinshipToHome } from '../../lib/kinship';
import { InteractionPanel } from './InteractionPanel';
import { SourcesTab } from './SourcesTab';

function Icon({ d, className = 'h-4 w-4' }: { d: string; className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" className={className}>
      <path d={d} stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function year(text: string | null | undefined): number | null {
  const m = /(\d{4})/.exec(text ?? '');
  return m ? Number(m[1]) : null;
}

function Avatar({ person, className }: { person: Person; className: string }) {
  return person.avatar_url ? (
    <img src={person.avatar_url} alt="" className={`${className} object-cover`} />
  ) : (
    <div className={`${className} flex items-center justify-center bg-fill-strong font-display font-semibold text-ink-soft`}>
      {person.display_name[0] ?? '?'}
    </div>
  );
}

function ActionChip({ label, d, onClick }: { label: string; d: string; onClick: () => void }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className="flex flex-col items-center gap-1.5 rounded-xl py-2 text-[10px] font-medium text-ink-muted transition hover:bg-fill hover:text-ink"
    >
      <span className="flex h-9 w-9 items-center justify-center rounded-full bg-fill text-ink-soft">
        <Icon d={d} />
      </span>
      {label}
    </button>
  );
}

function SectionLabel({ children }: { children: React.ReactNode }) {
  return <p className="text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-muted/80">{children}</p>;
}

function FamilyRow({ person, relation, onPick }: { person: Person; relation: string; onPick: (id: string) => void }) {
  return (
    <button
      type="button"
      onClick={() => onPick(person.id)}
      className="group flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition hover:bg-fill"
    >
      <Avatar person={person} className="h-9 w-9 shrink-0 rounded-full text-xs" />
      <span className="min-w-0 flex-1">
        <span className="block truncate text-[13px] font-medium text-ink group-hover:text-accent">{person.display_name}</span>
        <span className="block truncate text-[11px] text-ink-muted">
          {relation}
          {person.life_span ? ` · ${person.life_span}` : ''}
        </span>
      </span>
      <Icon d="m9 6 6 6-6 6" className="h-3.5 w-3.5 shrink-0 text-ink-muted/60 transition group-hover:text-accent" />
    </button>
  );
}

function VitalRow({ d, label, value, sub }: { d: string; label: string; value: string; sub?: string | null }) {
  return (
    <div className="flex items-start gap-3 py-2">
      <span className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-fill text-ink-muted">
        <Icon d={d} className="h-3.5 w-3.5" />
      </span>
      <div className="min-w-0">
        <p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-muted/80">{label}</p>
        <p className="text-[13px] leading-5 text-ink">{value}</p>
        {sub && <p className="text-[12px] leading-5 text-ink-muted">{sub}</p>}
      </div>
    </div>
  );
}

type Tab = 'profile' | 'events' | 'photos' | 'sources' | 'community';

// MyHeritage-style person panel: hero with kinship, action dock, and
// Profile / Events / Photos / Community tabs. Desktop: static column beside
// the canvas. Mobile: slide-in sheet that opens when a card is tapped.
export function PersonPanel({
  person,
  family,
  graph,
  homePersonId,
  isOwner,
  canManage,
  showInteractions,
  openSignal = 0,
  onPick,
  onEdit,
  onAddRelative,
  onEditPhoto,
  onConnect,
  onSetHome,
  onDelete,
}: {
  person: Person;
  family: FocusFamily;
  graph: FamilyGraph;
  homePersonId: string | null;
  isOwner: boolean;
  canManage: boolean;
  showInteractions?: boolean;
  openSignal?: number;
  onPick: (id: string) => void;
  onEdit: () => void;
  onAddRelative: () => void;
  onEditPhoto: () => void;
  onConnect: () => void;
  onSetHome: () => void;
  onDelete: () => void;
}) {
  const t = useT();
  const [tab, setTab] = useState<Tab>('profile');
  const [moreOpen, setMoreOpen] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const pushedHistory = useRef(false);

  // Every card tap re-opens the full-screen profile on mobile — including
  // tapping the same person again after closing it.
  useEffect(() => {
    if (openSignal > 0) {
      setMobileOpen(true);
      setMoreOpen(false);
    }
  }, [openSignal]);

  // Phone back button / back gesture closes the profile instead of leaving
  // the tree: opening it pushes a history entry, popping it closes the panel.
  useEffect(() => {
    if (!mobileOpen || !window.matchMedia('(max-width: 1023px)').matches) return;
    window.history.pushState({ oPersonPanel: true }, '');
    pushedHistory.current = true;
    const onPop = () => {
      pushedHistory.current = false;
      setMobileOpen(false);
    };
    window.addEventListener('popstate', onPop);
    return () => window.removeEventListener('popstate', onPop);
  }, [mobileOpen]);

  const closePanel = () => {
    if (pushedHistory.current) window.history.back();
    else setMobileOpen(false);
  };

  const { data: profile } = useQuery({
    queryKey: ['person-profile', person.id],
    queryFn: () => peopleApi.profile(person.id),
  });

  const { data: photos } = useQuery({
    queryKey: ['person-media', person.family_tree_id, person.id],
    queryFn: () =>
      mediaApi
        .treeList(person.family_tree_id, { kind: 'images', linked: 'linked' })
        .then((items) => items.filter((m) => m.person_id === person.id)),
  });

  const home = homePersonId ? graph.peopleById.get(homePersonId) : undefined;
  const kinship = useMemo(
    () => (home && !isOwner ? kinshipToHome(graph, home.id, person) : null),
    [graph, home, person, isOwner],
  );

  const birthYear = year(person.birth_date ?? person.birth_date_text);
  const deathYear = year(person.death_date ?? person.death_date_text);
  const age = person.is_living
    ? birthYear
      ? new Date().getFullYear() - birthYear
      : null
    : birthYear && deathYear
      ? deathYear - birthYear
      : null;

  const eventCount = (profile?.events.length ?? 0) + (profile?.relationship_facts.length ?? 0);

  const tabs: { key: Tab; label: string; count?: number }[] = [
    { key: 'profile', label: t('Profile') },
    { key: 'events', label: t('Events'), count: eventCount },
    { key: 'photos', label: t('Photos'), count: photos?.length },
    { key: 'sources', label: t('Sources') },
    ...(showInteractions ? [{ key: 'community' as Tab, label: t('Community') }] : []),
  ];

  const moreMenuItems = (
    <>
      {!isOwner && (
        <button
          onClick={() => {
            setMoreOpen(false);
            onSetHome();
          }}
          className="o-menu-item"
        >
          {t('Set as home person')}
        </button>
      )}
      <button
        onClick={() => {
          setMoreOpen(false);
          onConnect();
        }}
        className="o-menu-item"
      >
        {t('Connect to existing person')}
      </button>
      <button
        onClick={() => {
          setMoreOpen(false);
          onDelete();
        }}
        className="o-menu-item text-danger hover:text-danger-strong"
      >
        {t('Remove from tree')}
      </button>
    </>
  );

  const familyGroups: { relation: string; people: Person[] }[] = [
    { relation: person.sex === 'male' ? t('Father · Mother') : t('Parent'), people: family.parents },
    { relation: t('Sibling'), people: family.siblings },
    { relation: t('Partner'), people: family.spouses },
    { relation: t('Child'), people: family.children },
  ];

  return (
    <>
      {/* Mobile: floating person chip to reopen the sheet */}
      {!mobileOpen && (
        <button
          type="button"
          onClick={() => setMobileOpen(true)}
          className="fixed bottom-[4.5rem] left-3 z-30 flex max-w-[70vw] items-center gap-2.5 rounded-full border border-edge bg-elevated py-1.5 pl-1.5 pr-4 o-pop lg:hidden"
          aria-label={t('Open panel for {name}', { name: person.display_name })}
        >
          <Avatar person={person} className="h-8 w-8 rounded-full text-xs" />
          <span className="truncate text-[13px] font-semibold text-ink">{person.display_name}</span>
        </button>
      )}

      <aside
        className={`relative flex w-[320px] min-w-[320px] max-w-[320px] flex-col border-r border-line bg-inset
          max-lg:fixed max-lg:inset-0 max-lg:z-50 max-lg:w-full max-lg:min-w-0 max-lg:max-w-none max-lg:border-r-0 max-lg:transition-transform max-lg:duration-200
          ${mobileOpen ? 'max-lg:translate-x-0' : 'max-lg:translate-x-full'}`}
        aria-label={t('Details for {name}', { name: person.display_name })}
      >
        {/* ── Mobile page bar ── */}
        <div className="flex h-12 shrink-0 items-center gap-1 border-b border-line px-2 lg:hidden">
          <button
            type="button"
            onClick={closePanel}
            className="flex h-10 w-10 items-center justify-center rounded-full text-ink-soft transition hover:bg-fill"
            aria-label={t('Back to tree')}
          >
            <Icon d="M19 12H5m7-7-7 7 7 7" className="h-5 w-5" />
          </button>
          <span className="text-[15px] font-semibold text-ink">{t('Profile')}</span>
          <span className="flex-1" />
          {canManage && (
            <div className="relative">
              <button
                type="button"
                onClick={() => setMoreOpen((v) => !v)}
                className="flex h-10 w-10 items-center justify-center rounded-full text-ink-soft transition hover:bg-fill"
                aria-label={t('More actions')}
                aria-expanded={moreOpen}
              >
                <Icon d="M12 5h.01M12 12h.01M12 19h.01" className="h-5 w-5" />
              </button>
              {moreOpen && (
                <div className="o-pop absolute right-0 top-full z-30 mt-1 w-60 overflow-hidden rounded-2xl border border-edge bg-elevated py-1">
                  {moreMenuItems}
                </div>
              )}
            </div>
          )}
        </div>
        {/* ── Hero ── */}
        <div className="border-b border-line px-5 pb-3 pt-5">
          <div className="flex items-start gap-4">
            <div className="relative shrink-0">
              <Avatar person={person} className={`h-16 w-16 rounded-2xl text-xl max-lg:h-20 max-lg:w-20 max-lg:rounded-3xl ${isOwner ? 'ring-2 ring-emerald-400' : ''}`} />
              {isOwner && (
                <span className="absolute -bottom-1.5 left-1/2 -translate-x-1/2 rounded-full bg-emerald-400 px-1.5 py-px text-[8px] font-bold uppercase tracking-wide text-emerald-950">
                  {t('Home')}
                </span>
              )}
            </div>
            <div className="min-w-0 flex-1">
              <h2 className="font-display text-[17px] font-semibold leading-snug tracking-tight text-ink max-lg:text-[21px]">{person.display_name}</h2>
              {person.life_span && <p className="mt-0.5 text-[12px] text-ink-muted">{person.life_span}</p>}
              <div className="mt-2 flex flex-wrap items-center gap-1.5">
                {isOwner && <span className="o-chip-brand">{t('This is you')}</span>}
                {kinship && <span className="o-chip-brand capitalize">{kinship.label}{home ? ` ${t('of {name}', { name: home.given_name })}` : ''}</span>}
                <span className="o-chip-muted">{person.is_living ? t('Living') : t('Deceased')}</span>
              </div>
              {(person.birth_date_text || person.birth_date || person.birth_place) && (
                <p className="mt-2 text-[12.5px] leading-5 text-ink-soft lg:hidden">
                  {t('Born')}: {person.birth_date_text || person.birth_date || t('Unknown')}
                  {age !== null && person.is_living ? ` (${t('Age {age}', { age })})` : ''}
                  {person.birth_place ? ` · ${person.birth_place}` : ''}
                </p>
              )}
            </div>
          </div>

          {person.headline && <p className="mt-3 text-[12px] font-medium leading-5 text-ink-soft">{person.headline}</p>}

          {/* ── Action dock ── */}
          <div className={`mt-3 grid gap-1 ${canManage ? 'grid-cols-4' : 'grid-cols-2'}`}>
            {canManage && <ActionChip label={t('Edit')} d="M4 20h4l9.8-9.8a2.1 2.1 0 0 0-3-3L5 17v3Z" onClick={onEdit} />}
            {canManage && (
              <ActionChip label={t('Add')} d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM4.5 18c1.2-2.8 3.1-4.2 5.5-4.2M19 8v6M16 11h6" onClick={onAddRelative} />
            )}
            {canManage && <ActionChip label={t('Photo')} d="M4 7h3l2-2h6l2 2h3v12H4V7Zm8 9a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" onClick={onEditPhoto} />}
            <ActionChip label={t('Center')} d="M12 3v4m0 10v4M3 12h4m10 0h4M12 12h.01" onClick={() => onPick(person.id)} />
          </div>

          {canManage && (
            <div className="relative max-lg:hidden">
              <button
                type="button"
                onClick={() => setMoreOpen((v) => !v)}
                className="mt-1 flex w-full items-center justify-center gap-1.5 rounded-xl py-1.5 text-[11px] font-medium text-ink-muted transition hover:bg-fill hover:text-ink"
                aria-expanded={moreOpen}
              >
                {t('More actions')}
                <Icon d="m6 9 6 6 6-6" className="h-3 w-3" />
              </button>
              {moreOpen && (
                <div className="o-pop absolute inset-x-0 top-full z-20 mt-1 overflow-hidden rounded-2xl border border-edge bg-elevated py-1">
                  {moreMenuItems}
                </div>
              )}
            </div>
          )}
        </div>

        {/* ── Tabs ── */}
        <div className="flex gap-1 border-b border-line px-3 py-2 max-lg:gap-0 max-lg:px-0 max-lg:py-0" role="tablist" aria-label="Person sections">
          {tabs.map((t) => (
            <button
              key={t.key}
              role="tab"
              aria-selected={tab === t.key}
              onClick={() => setTab(t.key)}
              className={`text-[11.5px] font-semibold transition lg:rounded-full lg:px-3 lg:py-1.5 max-lg:flex-1 max-lg:border-b-2 max-lg:px-1 max-lg:py-3 max-lg:text-[13px] ${
                tab === t.key
                  ? 'text-accent max-lg:border-emerald-400 lg:bg-accent-soft'
                  : 'text-ink-muted hover:text-ink max-lg:border-transparent lg:hover:bg-fill'
              }`}
            >
              {t.label}
              {typeof t.count === 'number' && t.count > 0 && <span className="ml-1 opacity-60">{t.count}</span>}
            </button>
          ))}
        </div>

        {/* ── Content ── */}
        <div className="min-h-0 flex-1 overflow-y-auto px-5 py-4 max-lg:pb-28">
          {tab === 'profile' && (
            <div className="space-y-6">
              {/* Vitals */}
              <section>
                <SectionLabel>{t('Vital facts')}</SectionLabel>
                <div className="mt-1.5 divide-y divide-line/50">
                  <VitalRow
                    d="M12 3v18M3 12h18"
                    label={t('Born')}
                    value={person.birth_date_text || person.birth_date || t('Unknown')}
                    sub={person.birth_place}
                  />
                  {!person.is_living && (
                    <VitalRow
                      d="M6 21V7a6 6 0 0 1 12 0v14M6 21h12"
                      label={t('Died')}
                      value={person.death_date_text || person.death_date || t('Unknown')}
                      sub={person.death_place}
                    />
                  )}
                  {age !== null && (
                    <VitalRow
                      d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                      label={person.is_living ? t('Age') : t('Age at death')}
                      value={t('{age} years', { age })}
                    />
                  )}
                  {person.burial_place && <VitalRow d="M12 3v6m-3-3h6M7 21V9a5 5 0 0 1 10 0v12M5 21h14" label={t('Buried')} value={person.burial_place} />}
                  {person.cause_of_death && <VitalRow d="M12 9v4m0 4h.01M12 3l9 18H3L12 3Z" label={t('Cause of death')} value={person.cause_of_death} />}
                </div>
              </section>

              {/* About */}
              {person.notes && (
                <section>
                  <SectionLabel>{t('About')}</SectionLabel>
                  <p className="mt-2 whitespace-pre-line text-[12.5px] leading-6 text-ink-soft">{person.notes}</p>
                </section>
              )}

              {/* Family */}
              <section>
                <div className="flex items-center justify-between">
                  <SectionLabel>{t('Family')}</SectionLabel>
                  {canManage && (
                    <button type="button" onClick={onAddRelative} className="text-[11px] font-semibold text-accent hover:text-accent-strong">
                      {t('+ Add relative')}
                    </button>
                  )}
                </div>
                <div className="mt-1.5">
                  {familyGroups.some((g) => g.people.length) ? (
                    familyGroups.map((group) =>
                      group.people.map((m) => {
                        const rel = kinshipToHome(graph, person.id, m);
                        return <FamilyRow key={m.id} person={m} relation={rel?.label ?? group.relation} onPick={onPick} />;
                      }),
                    )
                  ) : (
                    <p className="py-2 text-[12.5px] text-ink-muted">{t('No linked relatives yet.')}</p>
                  )}
                </div>
              </section>

              {/* Imported record */}
              {profile && Object.keys(profile.imported_record).length > 0 && (
                <details className="group">
                  <summary className="cursor-pointer list-none">
                    <span className="flex items-center gap-1.5">
                      <SectionLabel>{t('Imported record')}</SectionLabel>
                      <Icon d="m6 9 6 6 6-6" className="h-3 w-3 text-ink-muted transition group-open:rotate-180" />
                    </span>
                  </summary>
                  <div className="mt-2 space-y-1.5 rounded-xl border border-line bg-fill-faint p-3 text-[12px] leading-5 text-ink-soft">
                    {Object.entries(profile.imported_record).map(([k, v]) => (
                      <div key={k}>
                        <span className="capitalize text-ink-muted">{k.replace(/_/g, ' ')}:</span> {v}
                      </div>
                    ))}
                  </div>
                </details>
              )}
            </div>
          )}

          {tab === 'events' && (
            <div className="space-y-6">
              {profile?.events.length ? (
                <ol>
                  {profile.events.map((e, i) => (
                    <li key={e.id} className="relative flex gap-3.5 pb-5 last:pb-0">
                      {i < profile.events.length - 1 && (
                        <span className="absolute left-[5px] top-5 h-full w-px bg-emerald-400/20" aria-hidden="true" />
                      )}
                      <span className="mt-1.5 h-[11px] w-[11px] shrink-0 rounded-full border-2 border-emerald-400/70 bg-inset" />
                      <div className="min-w-0">
                        <div className="flex flex-wrap items-baseline gap-x-2">
                          <p className="text-[13px] font-semibold text-ink">{e.label}</p>
                          {e.date && <p className="text-[11px] uppercase tracking-[0.12em] text-accent">{e.date}</p>}
                        </div>
                        {e.value && <p className="mt-0.5 text-[12px] text-ink-soft">{e.value}</p>}
                        {e.place && <p className="mt-0.5 text-[11.5px] text-ink-muted">{e.place}</p>}
                        {e.note && <p className="mt-0.5 text-[11.5px] text-ink-muted">{e.note}</p>}
                        {e.description && <p className="mt-1 text-[12px] leading-5 text-ink-soft">{e.description}</p>}
                      </div>
                    </li>
                  ))}
                </ol>
              ) : (
                <p className="text-[12.5px] text-ink-muted">{t('No timeline facts attached to this person yet.')}</p>
              )}

              {profile?.relationship_facts.length ? (
                <section>
                  <SectionLabel>{t('Relationships')}</SectionLabel>
                  <div className="mt-2 space-y-2">
                    {profile.relationship_facts.map((f) => (
                      <div key={f.id} className="rounded-xl border border-line bg-fill-faint p-3">
                        <p className="text-[13px] font-medium text-ink">{f.with ?? 'Relationship'}</p>
                        <p className="mt-0.5 text-[11.5px] text-ink-muted">
                          {[f.subtype, f.start && `from ${f.start}`, f.end && `until ${f.end}`, f.place].filter(Boolean).join(' · ')}
                        </p>
                        {f.description && <p className="mt-1.5 text-[12px] leading-5 text-ink-soft">{f.description}</p>}
                      </div>
                    ))}
                  </div>
                </section>
              ) : null}
            </div>
          )}

          {tab === 'photos' && (
            <div>
              {photos?.length ? (
                <div className="grid grid-cols-3 gap-1.5">
                  {photos.map((m) => (
                    <a
                      key={m.id}
                      href={m.preview_url ?? m.download_url}
                      target="_blank"
                      rel="noreferrer"
                      className="group relative block aspect-square overflow-hidden rounded-lg border border-edge-faint bg-fill"
                      title={m.title}
                    >
                      {m.preview_url ? (
                        <img src={m.preview_url} alt={m.title} loading="lazy" className="h-full w-full object-cover transition group-hover:scale-105" />
                      ) : (
                        <span className="flex h-full items-center justify-center text-ink-muted">
                          <Icon d="M4 7h3l2-2h6l2 2h3v12H4V7Z" />
                        </span>
                      )}
                      {m.is_primary && (
                        <span className="absolute left-1 top-1 rounded-full bg-emerald-400 px-1.5 py-px text-[8px] font-bold uppercase text-emerald-950">
                          {t('Profile')}
                        </span>
                      )}
                    </a>
                  ))}
                </div>
              ) : (
                <p className="text-[12.5px] text-ink-muted">{t('No photos of {name} yet.', { name: person.given_name })}</p>
              )}
              {canManage && (
                <button type="button" onClick={onEditPhoto} className="o-btn-secondary o-btn-sm mt-4 w-full">
                  {t('Add a photo')}
                </button>
              )}
            </div>
          )}

          {tab === 'sources' && (
            <SourcesTab personId={person.id} treeId={person.family_tree_id} canManage={canManage} />
          )}

          {tab === 'community' && showInteractions && <InteractionPanel person={person} />}
        </div>

        {/* Mobile: add-relative FAB, MyHeritage-style */}
        {canManage && mobileOpen && (
          <button
            type="button"
            onClick={onAddRelative}
            className="absolute bottom-6 right-4 z-20 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-400 text-emerald-950 shadow-[0_10px_28px_-6px_rgba(52,211,153,.65)] transition active:scale-95 lg:hidden"
            aria-label={t('Add a relative')}
          >
            <Icon d="M12 5v14M5 12h14" className="h-6 w-6" />
          </button>
        )}
      </aside>
    </>
  );
}
