import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminApi, SITE_ROLES } from '@core/api/endpoints/admin';
import { AppLayout } from '../components/AppLayout';
import { FullScreenSpinner } from '../components/Spinner';

const TABS = ['Dashboard', 'Users', 'Trees', 'Activity'] as const;
type Tab = (typeof TABS)[number];

function Dashboard() {
  const { data, isLoading } = useQuery({ queryKey: ['admin', 'dashboard'], queryFn: adminApi.dashboard });
  if (isLoading || !data) return <FullScreenSpinner />;
  const cards = [
    { label: 'Users', value: data.users },
    { label: 'Trees', value: data.trees },
    { label: 'People', value: data.people },
  ];
  return (
    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
      {cards.map((c) => (
        <div key={c.label} className="o-stat">
          <dt className="o-stat-label">{c.label}</dt>
          <dd className="o-stat-value">{c.value}</dd>
        </div>
      ))}
    </div>
  );
}

function Users() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const { data: users, isLoading } = useQuery({ queryKey: ['admin', 'users', search], queryFn: () => adminApi.users(search) });
  const invalidate = () => qc.invalidateQueries({ queryKey: ['admin', 'users'] });
  const setRole = useMutation({ mutationFn: ({ id, role }: { id: string; role: string }) => adminApi.updateRole(id, role), onSuccess: invalidate });
  const remove = useMutation({ mutationFn: (id: string) => adminApi.deleteUser(id), onSuccess: invalidate });

  return (
    <div>
      <input placeholder="Search users…" value={search} onChange={(e) => setSearch(e.target.value)} className="o-input mb-4 max-w-xs rounded-full" />
      {isLoading ? (
        <FullScreenSpinner />
      ) : (
        <table className="o-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Trees</th>
              <th>Role</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {users?.map((u) => (
              <tr key={u.id}>
                <td className="font-medium text-ink">{u.name}</td>
                <td className="text-ink-muted">{u.email}</td>
                <td>{u.family_trees_count}</td>
                <td>
                  <select
                    defaultValue={u.roles[0] ?? 'member'}
                    onChange={(e) => setRole.mutate({ id: u.id, role: e.target.value })}
                    className="rounded-full border border-line-strong bg-surface px-2.5 py-1 text-xs text-ink-soft outline-none focus:border-emerald-400"
                  >
                    {SITE_ROLES.map((r) => (
                      <option key={r} value={r}>
                        {r}
                      </option>
                    ))}
                  </select>
                </td>
                <td className="text-right">
                  <button
                    onClick={() => window.confirm(`Delete ${u.name}?`) && remove.mutate(u.id)}
                    className="text-xs font-medium text-danger hover:text-danger-strong hover:underline"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}

function Trees() {
  const qc = useQueryClient();
  const { data: trees, isLoading } = useQuery({ queryKey: ['admin', 'trees'], queryFn: adminApi.trees });
  const invalidate = () => qc.invalidateQueries({ queryKey: ['admin', 'trees'] });
  const toggle = useMutation({ mutationFn: (id: string) => adminApi.toggleGlobal(id), onSuccess: invalidate });
  const remove = useMutation({ mutationFn: (id: string) => adminApi.deleteTree(id), onSuccess: invalidate });

  if (isLoading) return <FullScreenSpinner />;
  return (
    <table className="o-table">
      <thead>
        <tr>
          <th>Tree</th>
          <th>Owner</th>
          <th>People</th>
          <th>Global</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {trees?.map((t) => (
          <tr key={t.id}>
            <td className="font-medium text-ink">{t.name}</td>
            <td className="text-ink-muted">{t.owner}</td>
            <td>{t.people_count}</td>
            <td>
              <button onClick={() => toggle.mutate(t.id)} className={t.global_tree_enabled ? 'o-chip-brand' : 'o-chip-muted'}>
                {t.global_tree_enabled ? 'On' : 'Off'}
              </button>
            </td>
            <td className="text-right">
              <button onClick={() => window.confirm(`Delete ${t.name}?`) && remove.mutate(t.id)} className="text-xs font-medium text-danger hover:text-danger-strong hover:underline">
                Delete
              </button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function Activity() {
  const { data, isLoading } = useQuery({ queryKey: ['admin', 'activity'], queryFn: adminApi.activity });
  if (isLoading) return <FullScreenSpinner />;
  return (
    <ul className="divide-y divide-line/70 text-sm">
      {data?.map((a) => (
        <li key={a.id} className="flex flex-wrap justify-between gap-x-4 gap-y-1 py-2.5">
          <span className="text-ink-soft">{a.description ?? a.event}</span>
          <span className="text-ink-muted">{a.causer ?? 'system'} · {a.created_at?.slice(0, 10)}</span>
        </li>
      ))}
    </ul>
  );
}

export function Admin() {
  const [tab, setTab] = useState<Tab>('Dashboard');
  return (
    <AppLayout>
      <div className="space-y-7">
        <header className="max-w-2xl space-y-2">
          <p className="o-eyebrow">Site administration</p>
          <h1 className="o-display text-3xl sm:text-4xl">Administration</h1>
        </header>

        <div className="o-subnav" role="tablist" aria-label="Admin sections">
          {TABS.map((t) => (
            <button
              key={t}
              role="tab"
              aria-selected={tab === t}
              onClick={() => setTab(t)}
              className={`o-subnav-link ${tab === t ? 'is-active' : ''}`}
            >
              {t}
            </button>
          ))}
        </div>

        {tab === 'Dashboard' ? (
          <Dashboard />
        ) : (
          <section className="o-card overflow-x-auto p-5 sm:p-6">
            {tab === 'Users' && <Users />}
            {tab === 'Trees' && <Trees />}
            {tab === 'Activity' && <Activity />}
          </section>
        )}
      </div>
    </AppLayout>
  );
}
