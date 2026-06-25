import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { peopleApi } from '@core/api/endpoints/people';
import type { Person } from '@core/models';
import type { FocusFamily } from '@core/tree/graph';
import { InteractionPanel } from './InteractionPanel';

function Icon({ d }: { d: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" className="workspace-inline-icon">
      <path d={d} stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

// Faithful port of trees/partials/sidebar-inner.blade.php: person hero + action dock + sections.
export function PersonPanel({
  person,
  family,
  isOwner,
  canManage,
  showInteractions,
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
  isOwner: boolean;
  canManage: boolean;
  showInteractions?: boolean;
  onPick: (id: string) => void;
  onEdit: () => void;
  onAddRelative: () => void;
  onEditPhoto: () => void;
  onConnect: () => void;
  onSetHome: () => void;
  onDelete: () => void;
}) {
  const [moreOpen, setMoreOpen] = useState(false);
  const immediate = [...family.parents, ...family.spouses, ...family.siblings, ...family.children];
  const { data: profile } = useQuery({
    queryKey: ['person-profile', person.id],
    queryFn: () => peopleApi.profile(person.id),
  });

  return (
    <aside className="workspace-sidebar flex w-[300px] min-w-[300px] max-w-[300px] flex-col overflow-y-auto border-r border-[#dfdfdf] bg-white">
      {/* Hero */}
      <div className="border-b border-[#ececec] px-6 py-7">
        <div className="flex items-start gap-5">
          {person.avatar_url ? (
            <img src={person.avatar_url} alt="" className="workspace-profile-avatar object-cover" />
          ) : (
            <div className="workspace-profile-avatar">{person.display_name[0] ?? '?'}</div>
          )}
          <div className="min-w-0 flex-1">
            <div className="flex items-start justify-between gap-3">
              <h1 className="workspace-person-hero-name">{person.display_name}</h1>
              {isOwner && <span className="workspace-person-badge">You</span>}
            </div>

            <div className="workspace-person-vitals">
              <div className="workspace-person-vital">
                <span className="workspace-person-vital-icon">
                  <Icon d="M12 3v18M3 12h18" />
                </span>
                <div className="min-w-0">
                  <div className="workspace-person-vital-date">
                    {person.birth_date_text || person.birth_date || 'Birth date unknown'}
                  </div>
                  {person.birth_place && <div className="workspace-person-vital-place">{person.birth_place}</div>}
                </div>
              </div>
              {!person.is_living && (person.death_date_text || person.death_place) && (
                <div className="workspace-person-vital">
                  <span className="workspace-person-vital-icon">
                    <Icon d="M6 21V7a6 6 0 0 1 12 0v14" />
                  </span>
                  <div className="min-w-0">
                    {(person.death_date_text || person.death_date) && (
                      <div className="workspace-person-vital-date">{person.death_date_text || person.death_date}</div>
                    )}
                    {person.death_place && <div className="workspace-person-vital-place">{person.death_place}</div>}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Action dock */}
        <div className="workspace-action-dock">
          <div className="workspace-action-chip is-active">
            <span className="workspace-action-chip-icon">
              <Icon d="M4 5h16v14H4zM9 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
            </span>
            <span>Profile</span>
          </div>
          {canManage && (
            <button type="button" className="workspace-action-chip" onClick={onEdit}>
              <span className="workspace-action-chip-icon">
                <Icon d="M4 20h4l9.8-9.8a2.1 2.1 0 0 0-3-3L5 17v3Z" />
              </span>
              <span>Edit</span>
            </button>
          )}
          {canManage && (
            <button type="button" className="workspace-action-chip" onClick={onAddRelative}>
              <span className="workspace-action-chip-icon">
                <Icon d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM4.5 18c1.2-2.8 3.1-4.2 5.5-4.2M19 8v6M16 11h6" />
              </span>
              <span>Add</span>
            </button>
          )}
          <button type="button" className="workspace-action-chip" onClick={() => setMoreOpen((v) => !v)}>
            <span className="workspace-action-chip-icon">
              <Icon d="M5 12h.01M12 12h.01M19 12h.01" />
            </span>
            <span>More</span>
          </button>
        </div>

        {moreOpen && (
          <div className="mt-3 overflow-hidden rounded-2xl border border-[#e4e4e4] bg-white shadow-[0_4px_16px_rgba(0,0,0,0.08)] text-[14px]">
            <button
              onClick={() => {
                setMoreOpen(false);
                onPick(person.id);
              }}
              className="flex w-full items-center gap-3 border-b border-[#f0f0f0] px-4 py-3.5 text-left text-[#2c3640] transition-colors hover:bg-[#f7f7f7]"
            >
              Center on this person
            </button>
            {canManage && !isOwner && (
              <button
                onClick={() => {
                  setMoreOpen(false);
                  onSetHome();
                }}
                className="flex w-full items-center gap-3 border-b border-[#f0f0f0] px-4 py-3.5 text-left text-[#2c3640] transition-colors hover:bg-[#f7f7f7]"
              >
                Set as home person
              </button>
            )}
            {canManage && (
              <button
                onClick={() => {
                  setMoreOpen(false);
                  onEditPhoto();
                }}
                className="flex w-full items-center gap-3 border-b border-[#f0f0f0] px-4 py-3.5 text-left text-[#2c3640] transition-colors hover:bg-[#f7f7f7]"
              >
                Edit photo
              </button>
            )}
            {canManage && (
              <button
                onClick={() => {
                  setMoreOpen(false);
                  onConnect();
                }}
                className="flex w-full items-center gap-3 border-b border-[#f0f0f0] px-4 py-3.5 text-left text-[#2c3640] transition-colors hover:bg-[#f7f7f7]"
              >
                Connect to existing person
              </button>
            )}
            {canManage && (
              <button
                onClick={() => {
                  setMoreOpen(false);
                  onDelete();
                }}
                className="flex w-full items-center gap-3 px-4 py-3.5 text-left text-[#c0392b] transition-colors hover:bg-[#fff5f5]"
              >
                Remove from tree
              </button>
            )}
          </div>
        )}
      </div>

      {/* About */}
      {(person.headline || person.notes) && (
        <section className="workspace-panel-section">
          <div className="workspace-section-title">About</div>
          {person.headline && <p className="mt-3 text-[13px] font-medium text-[#40505c]">{person.headline}</p>}
          {person.notes && <p className="mt-2 text-[12px] leading-5 text-[#5d6974]">{person.notes}</p>}
        </section>
      )}

      {/* Immediate family */}
      <section className="workspace-panel-section">
        <div className="workspace-section-title">Immediate family</div>
        <div className="mt-4 space-y-2">
          {immediate.length ? (
            immediate.map((m) => (
              <button
                key={m.id}
                onClick={() => onPick(m.id)}
                className="block w-full rounded-xl border border-[#ececec] bg-[#fafafa] px-3 py-2 text-left text-[13px] text-[#566472] hover:bg-white"
              >
                {m.display_name}
              </button>
            ))
          ) : (
            <p className="text-[13px] text-[#8b97a0]">No linked relatives yet.</p>
          )}
        </div>
      </section>

      {/* Timeline & facts */}
      <section className="workspace-panel-section">
        <div className="workspace-section-title">Timeline &amp; facts ({profile?.events.length ?? 0})</div>
        {profile?.events.length ? (
          <div className="mt-4 space-y-3">
            {profile.events.slice(0, 10).map((e) => (
              <div key={e.id} className="workspace-list-card">
                <div className="flex items-start justify-between gap-3">
                  <div className="font-medium text-[#40505c]">{e.label}</div>
                  {e.date && <div className="text-[11px] uppercase tracking-[0.14em] text-[#7b8791]">{e.date}</div>}
                </div>
                {e.value && <div className="mt-1 text-[12px] text-[#5d6974]">{e.value}</div>}
                {e.place && <div className="mt-1 text-[11px] text-[#7b8791]">{e.place}</div>}
                {e.note && <div className="mt-1 text-[11px] text-[#7b8791]">{e.note}</div>}
                {e.description && <p className="mt-2 text-[12px] leading-5 text-[#5d6974]">{e.description}</p>}
              </div>
            ))}
          </div>
        ) : (
          <p className="mt-4 text-[13px] text-[#8b97a0]">No timeline facts attached to this person yet.</p>
        )}
      </section>

      {/* Relationship facts */}
      {profile?.relationship_facts.length ? (
        <section className="workspace-panel-section">
          <div className="workspace-section-title">Relationship facts</div>
          <div className="mt-4 space-y-3">
            {profile.relationship_facts.map((f) => (
              <div key={f.id} className="workspace-list-card">
                <div className="font-medium text-[#40505c]">Relationship with {f.with}</div>
                <div className="mt-1 text-[11px] text-[#7b8791]">
                  {[f.subtype, f.start && `From: ${f.start}`, f.end && `Until: ${f.end}`, f.place]
                    .filter(Boolean)
                    .join(' · ')}
                </div>
                {f.description && <p className="mt-2 text-[12px] leading-5 text-[#5d6974]">{f.description}</p>}
              </div>
            ))}
          </div>
        </section>
      ) : null}

      {/* Imported record */}
      {profile && Object.keys(profile.imported_record).length > 0 && (
        <section className="workspace-panel-section">
          <div className="workspace-section-title">Imported record</div>
          <div className="mt-4 space-y-2 text-[12px] text-[#5d6974]">
            {Object.entries(profile.imported_record).map(([k, v]) => (
              <div key={k}>
                <span className="text-[#9aa3ab]">{k.replace(/_/g, ' ')}:</span> {v}
              </div>
            ))}
          </div>
        </section>
      )}

      {showInteractions && (
        <div className="px-6 pb-6">
          <InteractionPanel person={person} />
        </div>
      )}
    </aside>
  );
}
