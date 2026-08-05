"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { Badge, Card, EmptyState, PageHeader, Spinner } from "@/components/ui";
import { api } from "@/lib/api";
import type { ArticleDetail } from "@/types";

export default function ArticleDetailPage() {
  const params = useParams<{ id: string }>();
  const [article, setArticle] = useState<ArticleDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    api
      .get<{ data: ArticleDetail }>(`/articles/${params.id}`)
      .then((res) => setArticle(res.data))
      .catch(() => setNotFound(true))
      .finally(() => setLoading(false));
  }, [params.id]);

  if (loading) return <Spinner />;
  if (notFound || !article) return <EmptyState message="Artikel tidak ditemukan" />;

  return (
    <div className="max-w-3xl">
      <Link href="/articles" className="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">
        ← Kembali
      </Link>

      <PageHeader
        title={article.title}
        subtitle={`${article.source?.name ?? "-"} • ${article.published_at ? new Date(article.published_at).toLocaleString("id-ID") : "-"}`}
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Badge sentiment={article.sentiment?.label ?? null} />
        {article.sentiment ? (
          <span className="text-sm text-gray-500">
            confidence {Math.round(article.sentiment.confidence * 100)}%
          </span>
        ) : null}
        {article.topic ? (
          <span className="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
            {article.topic}
          </span>
        ) : null}
        {article.url ? (
          <a
            href={article.url}
            target="_blank"
            rel="noreferrer"
            className="text-sm font-medium text-indigo-600 hover:underline"
          >
            Buka sumber asli →
          </a>
        ) : null}
      </div>

      <Card className="mb-6">
        <p className="whitespace-pre-wrap leading-relaxed text-gray-700">
          {article.content || "Konten tidak tersedia."}
        </p>
      </Card>

      {article.entities && article.entities.length > 0 ? (
        <Card>
          <h2 className="mb-3 text-sm font-semibold text-gray-700">Entitas Terdeteksi</h2>
          <div className="flex flex-wrap gap-2">
            {article.entities.map((entity, index) => (
              <span
                key={index}
                className="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"
              >
                <span className="text-gray-400">{entity.type}</span>
                {entity.text}
              </span>
            ))}
          </div>
        </Card>
      ) : null}
    </div>
  );
}
