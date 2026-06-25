import type { RelationRole } from '@core/api/endpoints/people';
import type { Person } from '@core/models';

// MyHeritage-style radial chooser: the anchor person sits in the middle and the
// possible relatives radiate around it. Picking one hands the role back so the
// caller can open the add-person form.
type Slot = { role: RelationRole; label: string; tone: 'male' | 'female' | 'neutral' };

const TONE_BORDER: Record<Slot['tone'], string> = {
  male: '#6fcfe6',
  female: '#f1a4bd',
  neutral: '#c9d2db',
};

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

export function AddRelativeOverlay({
  anchor,
  onChoose,
  onClose,
}: {
  anchor: Person;
  onChoose: (role: RelationRole) => void;
  onClose: () => void;
}) {
  const father: Slot = { role: 'father', label: 'Add father', tone: 'male' };
  const mother: Slot = { role: 'mother', label: 'Add mother', tone: 'female' };
  const brother: Slot = { role: 'brother', label: 'Add brother', tone: 'male' };
  const sister: Slot = { role: 'sister', label: 'Add sister', tone: 'female' };
  const partner: Slot = { role: 'partner', label: 'Add another partner', tone: 'neutral' };
  const son: Slot = { role: 'son', label: 'Add son', tone: 'male' };
  const daughter: Slot = { role: 'daughter', label: 'Add daughter', tone: 'female' };

  return (
    <div
      className="fixed inset-0 z-40 flex flex-col items-center justify-center bg-[#1c1f24]/[0.92] p-6"
      onClick={onClose}
    >
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
          <RelativeButton slot={father} onChoose={onChoose} />
          <RelativeButton slot={mother} onChoose={onChoose} />
        </div>

        {/* Siblings · anchor · partner */}
        <div className="flex items-center gap-8">
          <div className="flex flex-col gap-3">
            <RelativeButton slot={brother} onChoose={onChoose} />
            <RelativeButton slot={sister} onChoose={onChoose} />
          </div>

          <div
            className="flex w-52 items-center gap-3 rounded-2xl border-2 px-4 py-3 shadow-2xl"
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

          <RelativeButton slot={partner} onChoose={onChoose} />
        </div>

        {/* Children */}
        <div className="flex gap-10">
          <RelativeButton slot={son} onChoose={onChoose} />
          <RelativeButton slot={daughter} onChoose={onChoose} />
        </div>
      </div>
    </div>
  );
}
