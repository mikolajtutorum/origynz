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
    <div className="grid grid-cols-3 gap-4">
      {cards.map((c) => (
        <div key={c.label} className="rounded-xl border border-neutral-200 bg-white p-5">
          <dt className="text-sm text-neutral-500">{c.label}</dt>
          <dd className="mt-1 text-3xl font-semibold text-neutral-900">{c.value}</dd>
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
      <input placeholder="Search users…" value={search} onChange={(e) => setSearch(e.target.value)} className="mb-4 rounded-md border border-neutral-300 px-3 py-2 text-sm" />
      {isLoading ? (
        <FullScreenSpinner />
      ) : (
        <table className="w-full text-sm">
          <thead className="text-left text-neutral-400">
            <tr>
              <th className="py-2">Name</th>
              <th>Email</th>
              <th>Trees</th>
              <th>Role</th>
              <th></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-neutral-100">
            {users?.map((u) => (
              <tr key={u.id}>
                <td className="py-2 font-medium text-neutral-900">{u.name}</td>
                <td className="text-neutral-500">{u.email}</td>
                <td>{u.family_trees_count}</td>
                <td>
                  <select
                    defaultValue={u.roles[0] ?? 'member'}
                    onChange={(e) => setRole.mutate({ id: u.id, role: e.target.value })}
                    className="rounded-md border border-neutral-300 px-2 py-1 text-xs"
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
                    className="text-xs text-red-600 hover:underline"
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
    <table className="w-full text-sm">
      <thead className="text-left text-neutral-400">
        <tr>
          <th className="py-2">Tree</th>
          <th>Owner</th>
          <th>People</th>
          <th>Global</th>
          <th></th>
        </tr>
      </thead>
      <tbody className="divide-y divide-neutral-100">
        {trees?.map((t) => (
          <tr key={t.id}>
            <td className="py-2 font-medium text-neutral-900">{t.name}</td>
            <td className="text-neutral-500">{t.owner}</td>
            <td>{t.people_count}</td>
            <td>
              <button onClick={() => toggle.mutate(t.id)} className={t.global_tree_enabled ? 'text-green-600' : 'text-neutral-400'}>
                {t.global_tree_enabled ? 'On' : 'Off'}
              </button>
            </td>
            <td className="text-right">
              <button onClick={() => window.confirm(`Delete ${t.name}?`) && remove.mutate(t.id)} className="text-xs text-red-600 hover:underline">
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
    <ul className="divide-y divide-neutral-100 text-sm">
      {data?.map((a) => (
        <li key={a.id} className="flex justify-between py-2">
          <span className="text-neutral-800">{a.description ?? a.event}</span>
          <span className="text-neutral-400">{a.causer ?? 'system'} · {a.created_at?.slice(0, 10)}</span>
        </li>
      ))}
    </ul>
  );
}

export function Admin() {
  const [tab, setTab] = useState<Tab>('Dashboard');
  return (
    <AppLayout>
      <h1 className="mb-6 text-2xl font-semibold text-neutral-900">Administration</h1>
      <div className="mb-5 flex gap-1 border-b border-neutral-200">
        {TABS.map((t) => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={['px-4 py-2 text-sm', tab === t ? 'border-b-2 border-neutral-900 font-medium text-neutral-900' : 'text-neutral-500'].join(' ')}
          >
            {t}
          </button>
        ))}
      </div>
      {tab === 'Dashboard' && <Dashboard />}
      {tab === 'Users' && <Users />}
      {tab === 'Trees' && <Trees />}
      {tab === 'Activity' && <Activity />}
    </AppLayout>
  );
}
