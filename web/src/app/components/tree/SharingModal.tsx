import { useState } from 'react';
import { useT } from '@core/i18n';
import { useTreeMembers } from '@core/queries/trees';
import {
  useMembershipRequests,
  useTreeCollaborationMutations,
  useTreeInvitations,
} from '@core/queries/collaboration';
import type { AssignableLevel } from '@core/api/endpoints/collaboration';
import { Button, Modal } from '../ui';
import { Spinner } from '../Spinner';

const TABS = ['Members', 'Invitations', 'Requests'] as const;
type Tab = (typeof TABS)[number];

function levelBadge(level: string): string {
  return level === 'owner' ? 'o-chip-brand' : 'o-chip-muted';
}

function MembersTab({ treeId }: { treeId: string }) {
  const t = useT();
  const { data: members, isLoading } = useTreeMembers(treeId);
  const { updateMember, removeMember } = useTreeCollaborationMutations(treeId);

  if (isLoading) return <Spinner />;
  if (!members || members.length === 0) return <p className="text-sm text-ink-muted">{t('No members yet.')}</p>;

  return (
    <ul className="divide-y divide-line/70">
      {members.map((m) => (
        <li key={m.id} className="flex items-center justify-between gap-3 py-2.5">
          <div className="min-w-0">
            <p className="truncate text-sm font-medium text-ink">{m.name}</p>
            <p className="truncate text-xs text-ink-muted">{m.email}</p>
          </div>
          {m.access_level === 'owner' ? (
            <span className={`${levelBadge(m.access_level)} shrink-0`}>{t('Owner')}</span>
          ) : (
            <div className="flex shrink-0 items-center gap-2">
              <select
                value={m.access_level}
                onChange={(e) => updateMember.mutate({ userId: m.id, level: e.target.value as AssignableLevel })}
                className="rounded-full border border-line-strong bg-surface px-2.5 py-1 text-xs text-ink-soft outline-none focus:border-emerald-400"
              >
                <option value="manager">{t('Manager')}</option>
                <option value="observer">{t('Observer')}</option>
              </select>
              <button
                onClick={() => window.confirm(t('Remove {name}?', { name: m.name })) && removeMember.mutate(m.id)}
                className="text-xs font-medium text-danger hover:text-danger-strong hover:underline"
              >
                {t('Remove')}
              </button>
            </div>
          )}
        </li>
      ))}
    </ul>
  );
}

function InvitationsTab({ treeId }: { treeId: string }) {
  const t = useT();
  const { data: invitations, isLoading } = useTreeInvitations(treeId);
  const { invite, revokeInvitation } = useTreeCollaborationMutations(treeId);
  const [email, setEmail] = useState('');
  const [level, setLevel] = useState<AssignableLevel>('observer');
  const [error, setError] = useState<string | null>(null);

  const onInvite = async () => {
    setError(null);
    try {
      await invite.mutateAsync({ email, level });
      setEmail('');
    } catch (e) {
      const err = e as { validationErrors?: Record<string, string[]>; message?: string };
      setError(err.validationErrors?.email?.[0] ?? err.message ?? t('Could not send invitation.'));
    }
  };

  const pending = invitations?.filter((i) => i.status === 'pending') ?? [];

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
        <div className="flex-1">
          <label className="o-label mb-1 block">{t('Invite by email')}</label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="name@example.com"
            className="o-input"
          />
        </div>
        <select
          value={level}
          onChange={(e) => setLevel(e.target.value as AssignableLevel)}
          className="o-input sm:w-36"
        >
          <option value="observer">{t('Observer')}</option>
          <option value="manager">{t('Manager')}</option>
        </select>
        <Button onClick={onInvite} loading={invite.isPending} disabled={!email}>
          {t('Invite')}
        </Button>
      </div>
      {error && <div className="o-alert-error">{error}</div>}

      {isLoading ? (
        <Spinner />
      ) : pending.length > 0 ? (
        <ul className="divide-y divide-line/70">
          {pending.map((i) => (
            <li key={i.id} className="flex items-center justify-between gap-3 py-2.5">
              <div className="min-w-0">
                <p className="truncate text-sm text-ink">{i.email}</p>
                <p className="text-xs text-ink-muted">{t(i.access_level)} · {t('pending')}</p>
              </div>
              <button
                onClick={() => revokeInvitation.mutate(i.id)}
                className="shrink-0 text-xs font-medium text-danger hover:text-danger-strong hover:underline"
              >
                {t('Revoke')}
              </button>
            </li>
          ))}
        </ul>
      ) : (
        <p className="text-sm text-ink-muted">{t('No pending invitations.')}</p>
      )}
    </div>
  );
}

function RequestsTab({ treeId }: { treeId: string }) {
  const t = useT();
  const { data: requests, isLoading } = useMembershipRequests(treeId);
  const { reviewRequest } = useTreeCollaborationMutations(treeId);

  if (isLoading) return <Spinner />;
  const pending = requests?.filter((r) => r.status === 'pending') ?? [];
  if (pending.length === 0) return <p className="text-sm text-ink-muted">{t('No pending requests.')}</p>;

  return (
    <ul className="divide-y divide-line/70">
      {pending.map((r) => (
        <li key={r.id} className="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="min-w-0">
            <p className="truncate text-sm font-medium text-ink">{r.requester_name}</p>
            <p className="truncate text-xs text-ink-muted">{r.requester_email}</p>
            {r.note && <p className="mt-1 text-xs text-ink-soft">“{r.note}”</p>}
          </div>
          <div className="flex shrink-0 gap-2">
            <Button
              variant="secondary"
              onClick={() => reviewRequest.mutate({ id: r.id, decision: 'declined' })}
              className="o-btn-sm"
            >
              {t('Decline')}
            </Button>
            <Button onClick={() => reviewRequest.mutate({ id: r.id, decision: 'approved' })} className="o-btn-sm">
              {t('Approve')}
            </Button>
          </div>
        </li>
      ))}
    </ul>
  );
}

export function SharingModal({ treeId, onClose }: { treeId: string; onClose: () => void }) {
  const t = useT();
  const [tab, setTab] = useState<Tab>('Members');

  return (
    <Modal title={t('Share this tree')} onClose={onClose}>
      <div className="o-subnav mb-5" role="tablist" aria-label={t('Share this tree')}>
        {TABS.map((tabKey) => (
          <button
            key={tabKey}
            role="tab"
            aria-selected={tab === tabKey}
            onClick={() => setTab(tabKey)}
            className={`o-subnav-link ${tab === tabKey ? 'is-active' : ''}`}
          >
            {t(tabKey)}
          </button>
        ))}
      </div>

      {tab === 'Members' && <MembersTab treeId={treeId} />}
      {tab === 'Invitations' && <InvitationsTab treeId={treeId} />}
      {tab === 'Requests' && <RequestsTab treeId={treeId} />}
    </Modal>
  );
}
