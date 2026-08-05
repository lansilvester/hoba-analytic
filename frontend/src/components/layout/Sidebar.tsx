"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { api, clearSession, getUser } from "@/lib/api";
import type { User } from "@/types";

const NAV = [
  { href: "/dashboard", label: "Dashboard" },
  { href: "/articles", label: "Artikel" },
  { href: "/projects", label: "Proyek" },
  { href: "/reports", label: "Laporan" },
];

export default function Sidebar() {
  const pathname = usePathname();
  const router = useRouter();
  const user = getUser<User>();

  const logout = async () => {
    try {
      await api.post("/auth/logout");
    } catch {
      // token mungkin sudah invalid
    }
    clearSession();
    router.push("/login");
  };

  return (
    <aside className="flex h-screen w-60 flex-col border-r border-gray-200 bg-white">
      <div className="flex items-center gap-2 border-b border-gray-200 px-5 py-4">
        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">
          P
        </div>
        <div>
          <p className="text-sm font-bold text-gray-900">Pixel Joy</p>
          <p className="text-xs text-gray-400">Media Analytics</p>
        </div>
      </div>

      <nav className="flex-1 space-y-1 p-3">
        {NAV.map((item) => {
          const active = pathname.startsWith(item.href);
          return (
            <Link
              key={item.href}
              href={item.href}
              className={`block rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                active ? "bg-indigo-50 text-indigo-700" : "text-gray-600 hover:bg-gray-50"
              }`}
            >
              {item.label}
            </Link>
          );
        })}
      </nav>

      <div className="border-t border-gray-200 p-3">
        <p className="truncate px-3 text-sm font-medium text-gray-700">{user?.name ?? "User"}</p>
        <p className="px-3 text-xs text-gray-400">
          {user?.role ?? "-"}
          <span className="mx-1">•</span>
          {user?.email}
        </p>
        <button
          onClick={logout}
          className="mt-2 w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50"
        >
          Keluar
        </button>
      </div>
    </aside>
  );
}
