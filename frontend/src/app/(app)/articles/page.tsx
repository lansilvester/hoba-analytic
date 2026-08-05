"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Badge, Button, EmptyState, Input, PageHeader, Spinner } from "@/components/ui";
import { api } from "@/lib/api";
import type { Article, Paginated } from "@/types";

const SENTIMENTS = [
  { value: "", label: "Semua" },
  { value: "positive", label: "Positif" },
  { value: "negative", label: "Negatif" },
  { value: "neutral", label: "Netral" },
];

export default function ArticlesPage() {
  const searchParams = useSearchParams();
  const projectId = searchParams.get("project_id") ?? "";
  const [sentiment, setSentiment] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [data, setData] = useState<Paginated<Article> | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      setLoading(true);
      const params = new URLSearchParams({ per_page: "15", page: String(page) });
      if (sentiment) params.set("sentiment", sentiment);
      if (search.trim()) params.set("search", search.trim());
      if (projectId) params.set("project_id", projectId);

      try {
        setData(await api.get<Paginated<Article>>(`/articles?${params.toString()}`));
      } catch {
        setData(null);
      } finally {
        setLoading(false);
      }
    })();
  }, [sentiment, search, page, projectId]);

  const applySearch = () => {
    setPage(1);
  };

  return (
    <div>
      <PageHeader title="Artikel" subtitle={`${data?.meta.total ?? 0} artikel terpantau`} />

      <div className="mb-4 flex flex-wrap items-end gap-3">
        <div className="flex gap-2">
          {SENTIMENTS.map((s) => (
            <button
              key={s.value}
              onClick={() => {
                setSentiment(s.value);
                setPage(1);
              }}
              className={`rounded-full px-3 py-1.5 text-sm font-medium transition-colors ${
                sentiment === s.value
                  ? "bg-indigo-600 text-white"
                  : "bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50"
              }`}
            >
              {s.label}
            </button>
          ))}
        </div>
        <form
          className="flex-1 min-w-56"
          onSubmit={(e) => {
            e.preventDefault();
            applySearch();
          }}
        >
          <Input
            placeholder="Cari judul atau konten..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </form>
      </div>

      {loading ? (
        <Spinner />
      ) : data && data.data.length > 0 ? (
        <>
          <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                  <th className="px-4 py-3">Judul</th>
                  <th className="px-4 py-3">Sumber</th>
                  <th className="px-4 py-3">Sentimen</th>
                  <th className="px-4 py-3">Tanggal</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {data.data.map((article) => (
                  <tr key={article.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <Link href={`/articles/${article.id}`} className="font-medium text-gray-800 hover:text-indigo-600">
                        {article.title}
                      </Link>
                      <p className="text-xs text-gray-400">{article.project?.name}</p>
                    </td>
                    <td className="px-4 py-3 text-gray-600">{article.source?.name ?? "-"}</td>
                    <td className="px-4 py-3">
                      <Badge sentiment={article.sentiment} />
                    </td>
                    <td className="px-4 py-3 text-gray-500">
                      {article.published_at ? new Date(article.published_at).toLocaleDateString("id-ID") : "-"}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="mt-4 flex items-center justify-between">
            <p className="text-sm text-gray-500">
              Halaman {data.meta.current_page} dari {data.meta.last_page}
            </p>
            <div className="flex gap-2">
              <Button variant="secondary" disabled={!data.meta.current_page || data.meta.current_page <= 1} onClick={() => setPage((p) => p - 1)}>
                Sebelumnya
              </Button>
              <Button
                variant="secondary"
                disabled={data.meta.current_page >= data.meta.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Berikutnya
              </Button>
            </div>
          </div>
        </>
      ) : (
        <EmptyState message="Tidak ada artikel yang cocok dengan filter" />
      )}
    </div>
  );
}
