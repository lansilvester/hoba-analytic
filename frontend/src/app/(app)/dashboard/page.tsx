"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import TrendChart from "@/components/charts/TrendChart";
import { Badge, Card, EmptyState, PageHeader, Spinner } from "@/components/ui";
import { api } from "@/lib/api";
import type { Article, Paginated, TrendData } from "@/types";

export default function DashboardPage() {
  const [trends, setTrends] = useState<TrendData | null>(null);
  const [recent, setRecent] = useState<Paginated<Article> | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const [trendData, articleData] = await Promise.all([
          api.get<{ data: TrendData }>("/articles/trends"),
          api.get<Paginated<Article>>("/articles?per_page=5"),
        ]);
        setTrends(trendData.data);
        setRecent(articleData);
      } catch {
        // token kadaluarsa ditangani api client
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  if (loading) return <Spinner />;

  const totals: Record<string, number> = {};
  for (const s of trends?.series ?? []) {
    totals[s.name] = s.data.reduce((a, b) => a + b, 0);
  }
  const total = Object.values(totals).reduce((a, b) => a + b, 0);

  const stats = [
    { label: "Total Artikel", value: recent?.meta.total ?? total, color: "text-gray-900" },
    { label: "Positif", value: totals.positive ?? 0, color: "text-emerald-600" },
    { label: "Negatif", value: totals.negative ?? 0, color: "text-red-600" },
    { label: "Netral", value: totals.neutral ?? 0, color: "text-gray-500" },
  ];

  return (
    <div>
      <PageHeader title="Dashboard" subtitle="Ringkasan pantauan media 7 hari terakhir" />

      <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        {stats.map((stat) => (
          <Card key={stat.label}>
            <p className="text-sm text-gray-500">{stat.label}</p>
            <p className={`mt-1 text-3xl font-bold ${stat.color}`}>{stat.value}</p>
          </Card>
        ))}
      </div>

      <Card className="mb-6">
        <h2 className="mb-4 text-sm font-semibold text-gray-700">Tren Sentimen</h2>
        {trends && trends.labels?.length > 0 ? (
          <TrendChart labels={trends.labels} series={trends.series ?? []} />
        ) : (
          <EmptyState message="Belum ada data untuk ditampilkan" />
        )}
      </Card>

      <Card>
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-sm font-semibold text-gray-700">Artikel Terbaru</h2>
          <Link href="/articles" className="text-sm font-medium text-indigo-600 hover:underline">
            Lihat semua
          </Link>
        </div>
        {recent && recent.data?.length > 0 ? (
          <div className="divide-y divide-gray-100">
            {recent.data.map((article) => (
              <Link
                key={article.id}
                href={`/articles/${article.id}`}
                className="flex items-center justify-between gap-4 py-3 hover:bg-gray-50"
              >
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-gray-800">{article.title}</p>
                  <p className="text-xs text-gray-400">
                    {article.source?.name} • {article.published_at ? new Date(article.published_at).toLocaleString("id-ID") : "-"}
                  </p>
                </div>
                <Badge sentiment={article.sentiment} />
              </Link>
            ))}
          </div>
        ) : (
          <EmptyState message="Belum ada artikel" />
        )}
      </Card>
    </div>
  );
}
