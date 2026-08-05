"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Button, Card, Input, PageHeader } from "@/components/ui";
import { api, ApiError } from "@/lib/api";
import type { Source } from "@/types";

export default function NewProjectPage() {
  const router = useRouter();
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [keywords, setKeywords] = useState("");
  const [sources, setSources] = useState<Source[]>([]);
  const [selectedSources, setSelectedSources] = useState<number[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    api
      .get<{ data: Source[] }>("/sources")
      .then((res) => setSources(res.data))
      .catch(() => {});
  }, []);

  const toggleSource = (id: number) => {
    setSelectedSources((prev) => (prev.includes(id) ? prev.filter((s) => s !== id) : [...prev, id]));
  };

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await api.post("/projects", {
        name,
        description: description || null,
        keywords: keywords
          .split(",")
          .map((k) => k.trim())
          .filter(Boolean),
        source_ids: selectedSources,
      });
      router.push("/projects");
      router.refresh();
    } catch (err) {
      const apiError = err as ApiError;
      setError(apiError.errors ? Object.values(apiError.errors).flat().join(", ") : apiError.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-2xl">
      <PageHeader title="Proyek Baru" subtitle="Buat proyek monitoring media baru" />

      <Card>
        {error ? (
          <div className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>
        ) : null}

        <form onSubmit={submit} className="space-y-4">
          <Input
            label="Nama proyek"
            required
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Contoh: Reputasi Brand 2026"
          />
          <label className="block">
            <span className="mb-1 block text-sm font-medium text-gray-700">Deskripsi</span>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              rows={3}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              placeholder="Tujuan monitoring proyek ini..."
            />
          </label>
          <Input
            label="Keyword (pisahkan dengan koma)"
            required
            value={keywords}
            onChange={(e) => setKeywords(e.target.value)}
            placeholder="Pixel Joy, media monitoring, reputasi"
          />

          <div>
            <span className="mb-1 block text-sm font-medium text-gray-700">Sumber berita</span>
            <div className="flex flex-wrap gap-2">
              {sources.map((source) => {
                const selected = selectedSources.includes(source.id);
                return (
                  <button
                    key={source.id}
                    type="button"
                    onClick={() => toggleSource(source.id)}
                    className={`rounded-full px-3 py-1.5 text-sm font-medium transition-colors ${
                      selected
                        ? "bg-indigo-600 text-white"
                        : "bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50"
                    }`}
                  >
                    {source.name}
                  </button>
                );
              })}
            </div>
          </div>

          <div className="flex gap-2 pt-2">
            <Button type="submit" disabled={loading}>
              {loading ? "Menyimpan..." : "Simpan Proyek"}
            </Button>
            <Button type="button" variant="secondary" onClick={() => router.back()}>
              Batal
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
}
