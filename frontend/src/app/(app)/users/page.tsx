"use client";

import { useCallback, useEffect, useState } from "react";
import { Button, Card, EmptyState, Input, PageHeader, Spinner } from "@/components/ui";
import { api, ApiError } from "@/lib/api";
import type { Paginated, Role, Tenant, User } from "@/types";

type UserForm = {
  id?: number;
  name: string;
  email: string;
  password: string;
  role_id: string;
  tenant_id: string;
  is_active: boolean;
};

const EMPTY_FORM: UserForm = {
  name: "",
  email: "",
  password: "",
  role_id: "",
  tenant_id: "",
  is_active: true,
};

const roleBadge: Record<string, string> = {
  admin: "bg-indigo-100 text-indigo-800",
  editor: "bg-amber-100 text-amber-800",
  viewer: "bg-gray-100 text-gray-700",
};

export default function UsersPage() {
  const [users, setUsers] = useState<User[] | null>(null);
  const [roles, setRoles] = useState<Role[]>([]);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState<UserForm | null>(null);
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [userData, roleData, tenantData] = await Promise.all([
        api.get<Paginated<User>>(`/users?per_page=100${search ? `&search=${encodeURIComponent(search)}` : ""}`),
        api.get<{ data: Role[] }>("/roles"),
        api.get<{ data: Tenant[] }>("/tenants"),
      ]);
      setUsers(userData.data);
      setRoles(roleData.data);
      setTenants(tenantData.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Gagal memuat data pengguna");
    } finally {
      setLoading(false);
    }
  }, [search]);

  useEffect(() => {
    const timer = setTimeout(load, 0);
    return () => clearTimeout(timer);
  }, [load]);

  const openCreate = () => setForm({ ...EMPTY_FORM, role_id: roles[0]?.id.toString() ?? "" });
  const openEdit = (user: User) =>
    setForm({
      id: user.id,
      name: user.name,
      email: user.email,
      password: "",
      role_id: user.role_id?.toString() ?? "",
      tenant_id: user.tenant_id?.toString() ?? "",
      is_active: user.is_active ?? true,
    });

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form) return;
    setError(null);
    setSaving(true);
    try {
      const payload = {
        name: form.name,
        email: form.email,
        role_id: form.role_id ? Number(form.role_id) : null,
        tenant_id: form.tenant_id ? Number(form.tenant_id) : null,
        is_active: form.is_active,
        ...(form.password ? { password: form.password } : {}),
      };
      if (form.id) {
        await api.put(`/users/${form.id}`, payload);
      } else {
        await api.post("/users", payload);
      }
      setForm(null);
      await load();
    } catch (err) {
      const apiErr = err as ApiError;
      setError(apiErr.message);
    } finally {
      setSaving(false);
    }
  };

  const toggleActive = async (user: User) => {
    try {
      await api.put(`/users/${user.id}`, {
        name: user.name,
        email: user.email,
        role_id: user.role_id,
        tenant_id: user.tenant_id,
        is_active: !user.is_active,
      });
      await load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Gagal mengubah status");
    }
  };

  const remove = async (user: User) => {
    if (!confirm(`Hapus pengguna "${user.name}"?`)) return;
    try {
      await api.del(`/users/${user.id}`);
      await load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Gagal menghapus pengguna");
    }
  };

  return (
    <div>
      <PageHeader
        title="Pengguna"
        subtitle="Kelola akun dan peran dalam sistem"
        action={<Button onClick={openCreate}>+ Tambah Pengguna</Button>}
      />

      {error ? (
        <div className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>
      ) : null}

      <div className="mb-4">
        <Input
          placeholder="Cari nama atau email..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
      </div>

      {form ? (
        <Card className="mb-6">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">
            {form.id ? `Edit Pengguna: ${form.name}` : "Tambah Pengguna"}
          </h2>
          <form onSubmit={submit} className="grid gap-4 md:grid-cols-2">
            <Input
              label="Nama"
              required
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
            />
            <Input
              label="Email"
              type="email"
              required
              value={form.email}
              onChange={(e) => setForm({ ...form, email: e.target.value })}
            />
            <Input
              label={form.id ? "Kata sandi (kosongkan jika tidak diganti)" : "Kata sandi"}
              type="password"
              required={!form.id}
              minLength={8}
              value={form.password}
              onChange={(e) => setForm({ ...form, password: e.target.value })}
            />
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-gray-700">Peran</span>
              <select
                value={form.role_id}
                onChange={(e) => setForm({ ...form, role_id: e.target.value })}
                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              >
                <option value="">Tanpa peran</option>
                {roles.map((role) => (
                  <option key={role.id} value={role.id}>
                    {role.name}
                  </option>
                ))}
              </select>
            </label>
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-gray-700">Tenant</span>
              <select
                value={form.tenant_id}
                onChange={(e) => setForm({ ...form, tenant_id: e.target.value })}
                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              >
                <option value="">Global</option>
                {tenants.map((tenant) => (
                  <option key={tenant.id} value={tenant.id}>
                    {tenant.name}
                  </option>
                ))}
              </select>
            </label>
            <label className="flex items-center gap-2 md:col-span-2">
              <input
                type="checkbox"
                checked={form.is_active}
                onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                className="h-4 w-4 rounded border-gray-300 text-indigo-600"
              />
              <span className="text-sm text-gray-700">Akun aktif</span>
            </label>
            <div className="flex gap-2 md:col-span-2">
              <Button type="submit" disabled={saving}>
                {saving ? "Menyimpan..." : "Simpan"}
              </Button>
              <Button variant="secondary" onClick={() => setForm(null)}>
                Batal
              </Button>
            </div>
          </form>
        </Card>
      ) : null}

      {loading ? (
        <Spinner />
      ) : users && users.length > 0 ? (
        <Card>
          <div className="divide-y divide-gray-100">
            {users.map((user) => (
              <div key={user.id} className="flex items-center justify-between gap-4 py-3">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-gray-800">{user.name}</p>
                  <p className="text-xs text-gray-400">
                    {user.email} • {user.tenant?.name ?? "Global"}
                    {user.created_at ? ` • ${new Date(user.created_at).toLocaleDateString("id-ID")}` : ""}
                  </p>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  <span
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${roleBadge[user.role ?? ""] ?? "bg-gray-100 text-gray-700"}`}
                  >
                    {user.role ?? "-"}
                  </span>
                  <span
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                      user.is_active ? "bg-emerald-100 text-emerald-800" : "bg-red-100 text-red-700"
                    }`}
                  >
                    {user.is_active ? "Aktif" : "Nonaktif"}
                  </span>
                  <button onClick={() => openEdit(user)} className="text-sm font-medium text-indigo-600 hover:underline">
                    Edit
                  </button>
                  <button
                    onClick={() => toggleActive(user)}
                    className="text-sm font-medium text-gray-500 hover:underline"
                  >
                    {user.is_active ? "Nonaktifkan" : "Aktifkan"}
                  </button>
                  <Button variant="danger" onClick={() => remove(user)}>
                    Hapus
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </Card>
      ) : (
        <EmptyState message="Belum ada pengguna." />
      )}
    </div>
  );
}
