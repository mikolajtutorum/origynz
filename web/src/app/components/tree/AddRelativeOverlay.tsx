import type { RelationRole } from '@core/api/endpoints/people';
import type { Person } from '@core/models';

// Add-relative chooser. Desktop: radial layout with the anchor person in the
// middle and relatives around them. Mobile: a bottom action sheet with the
// same options as a thumb-friendly list.
type Slot = { role: RelationRole; label: string; tone: 'male' | 'female' | 'neutral' };

const TONE_BORDER: Record<Slot['tone'], string> = {
  male: '#6fcfe6',
  female: '#f1a4bd',
  neutral: '#c9d2db',
};

const SLOTS: { father: Slot; mother: Slot; brother: Slot; sister: Slot; partner: Slot; son: Slot; daughter: Slot } = {
  father: { role: 'father', label: 'Add father', tone: 'male' },
  mother: { role: 'mother', label: 'Add mother', tone: 'female' },
  brother: { role: 'brother', label: 'Add brother', tone: 'male' },
  sister: { role: 'sister', label: 'Add sister', tone: 'female' },
  partner: { role: 'partner', label: 'Add another partner', tone: 'neutral' },
  son: { role: 'son', label: 'Add son', tone: 'male' },
  daughter: { role: 'daughter', label: 'Add daughter', tone: 'female' },
};

const SHEET_ORDER: Slot[] = [
  SLOTS.father,
  SLOTS.mother,
  SLOTS.brother,
  SLOTS.sister,
  SLOTS.partner,
  SLOTS.son,
  SLOTS.daughter,
];

function PersonGlyph() {
  return (
    <svg viewBox="0 0 24 24" fill="none" className="h-5 w-5">
      <circle cx="12" cy="8" r="3.4" stroke="currentColor" strokeWidth="1.8" />
      <path d="M5 19c1.6-3.2 4-4.6 7-4.6s5.4 1.4 7 4.6" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
    </svg>
  );
}

function RelativeButton({ slot, onChoose }: { slot: Slot; onChoose: (role: RelationRole) => void }) {
  return (
    <button
      type="button"
      className="gen-relative-btn"
      style={{ borderColor: TONE_BORDER[slot.tone] }}
      onClick={() => onChoose(slot.role)}
    >
      <span className="gen-relative-btn-icon">
        <PersonGlyph />
      </span>
      {slot.label}
    </button>
  );
}

function AnchorCard({ anchor, compact }: { anchor: Person; compact?: boolean }) {
  return (
    <div
      className={`flex items-center gap-3 rounded-2xl border-2 px-4 py-3 ${compact ? '' : 'w-52 shadow-2xl'}`}
      style={{ borderColor: '#86d4a8', background: '#e9f8ef' }}
    >
      {anchor.avatar_url ? (
        <img src={anchor.avatar_url} alt="" className="h-11 w-11 shrink-0 rounded-full object-cover" />
      ) : (
        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#9ec9ad] text-sm font-semibold text-white">
          {(anchor.display_name[0] ?? '?').toUpperCase()}
        </span>
      )}
      <span className="min-w-0">
        <span className="block truncate text-[14px] font-semibold text-[#26303a]">{anchor.display_name}</span>
        {anchor.life_span && <span className="block truncate text-[12px] text-[#6b7682]">{anchor.life_span}</span>}
      </span>
    </div>
  );
}

export function AddRelativeOverlay({
  anchor,
  onChoose,
  onClose,
}: {
  anchor: Person;
  onChoose: (role: RelationRole) => void;
  onClose: () => void;
}) {
  return (
    <div className="fixed inset-0 z-50 bg-[#1c1f24]/[0.92]" onClick={onClose}>
      {/* ── Desktop: radial chooser ── */}
      <div className="hidden h-full flex-col items-center justify-center p-6 sm:flex">
        <button
          type="button"
          onClick={onClose}
          className="absolute left-1/2 top-7 flex -translate-x-1/2 items-center gap-2 text-[15px] text-white/80 hover:text-white"
        >
          Close <span className="text-xl leading-none">×</span>
        </button>

        <div className="flex flex-col items-center gap-5" onClick={(e) => e.stopPropagation()}>
          {/* Parents */}
          <div className="flex gap-10">
            <RelativeButton slot={SLOTS.father} onChoose={onChoose} />
            <RelativeButton slot={SLOTS.mother} onChoose={onChoose} />
          </div>

          {/* Siblings · anchor · partner */}
          <div className="flex items-center gap-8">
            <div className="flex flex-col gap-3">
              <RelativeButton slot={SLOTS.brother} onChoose={onChoose} />
              <RelativeButton slot={SLOTS.sister} onChoose={onChoose} />
            </div>

            <AnchorCard anchor={anchor} />

            <RelativeButton slot={SLOTS.partner} onChoose={onChoose} />
          </div>

          {/* Children */}
          <div className="flex gap-10">
            <RelativeButton slot={SLOTS.son} onChoose={onChoose} />
            <RelativeButton slot={SLOTS.daughter} onChoose={onChoose} />
          </div>
        </div>
      </div>

      {/* ── Mobile: bottom action sheet ── */}
      <div
        className="absolute inset-x-0 bottom-0 max-h-[85dvh] overflow-y-auto rounded-t-3xl bg-white p-4 pb-[calc(env(safe-area-inset-bottom)+1rem)] shadow-[0_-16px_48px_rgba(0,0,0,.35)] sm:hidden"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-label={`Add a relative of ${anchor.display_name}`}
      >
        <div className="mx-auto mb-3 h-1 w-10 rounded-full bg-[#d9dde2]" />
        <div className="mb-3 flex items-center justify-between gap-3">
          <AnchorCard anchor={anchor} compact />
          <button
            type="button"
            onClick={onClose}
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f1f3f5] text-xl leading-none text-[#4a5560]"
            aria-label="Close"
          >
            ×
          </button>
        </div>
        <p className="mb-2 px-1 text-[13px] font-medium text-[#6b7682]">Add a relative</p>
        <div className="flex flex-col gap-2">
          {SHEET_ORDER.map((slot) => (
            <button
              key={slot.role}
              type="button"
              onClick={() => onChoose(slot.role)}
              className="flex min-h-13 w-full items-center gap-3 rounded-2xl border-2 bg-white px-4 text-left text-[15px] font-medium text-[#26303a] active:bg-[#f7f9fb]"
              style={{ borderColor: TONE_BORDER[slot.tone] }}
            >
              <span className="gen-relative-btn-icon">
                <PersonGlyph />
              </span>
              {slot.label}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
