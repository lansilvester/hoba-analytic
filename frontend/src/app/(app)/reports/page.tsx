"use client";

import { useCallback, useEffect, useState } from "react";
import { Button, Card, EmptyState, Input, PageHeader, Spinner } from "@/components/ui";
import { api, ApiError } from "@/lib/api";
import type { Project, Report } from "@/types";

export default function ReportsPage() {
  const [reports, setReports] = useState<Report[] | null>(null);
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);
  const [generating, setGenerating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);

  const [projectId, setProjectId] = useState("");
  const [title, setTitle] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");

  const load = useCallback(async () => {
    try {
      const [reportData, projectData] = await Promise.all([
        api.get<{ data: Report[] }>("/reports"),
        api.get<{ data: Project[] }>("/projects"),
      ]);
      setReports(reportData.data);
      setProjects(projectData.data);
    } catch {
      setReports([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    const timer = setTimeout(load, 0);
    return () => clearTimeout(timer);
  }, [load]);

  const generate = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setGenerating(true);
    try {
      await api.post("/reports/generate", {
        project_id: Number(projectId),
        title,
        from: from || null,
        to: to || null,
        format: "pdf",
      });
      setShowForm(false);
      setTitle("");
      setFrom("");
      setTo("");
      await load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Gagal membuat laporan");
    } finally {
      setGenerating(false);
    }
  };

  const download = async (report: Report) => {
    try {
      const response = await fetch(`${report.download_url}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem("pja_token")}` },
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `${report.title || "laporan"}.pdf`;
      a.click();
      URL.revokeObjectURL(url);
    } catch (err) {
      alert(err instanceof Error ? err.message : "Gagal mengunduh laporan");
    }
  };

  const statusBadge = (status: string) => {
    const styles =
      status === "ready"
        ? "bg-emerald-100 text-emerald-800"
        : status === "failed"
          ? "bg-red-100 text-red-800"
          : "bg-amber-100 text-amber-800";
    return (
      <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${styles}`}>
        {status}
      </span>
    );
  };

  return (
    <div>
      <PageHeader
        title="Laporan"
        subtitle="Laporan analisis media per proyek"
        action={
          <Button onClick={() => setShowForm((v) => !v)} disabled={projects.length === 0}>
            {showForm ? "Batal" : "+ Buat Laporan"}
          </Button>
        }
      />

      {showForm ? (
        <Card className="mb-6">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">Generate Laporan</h2>
          {error ? (
            <div className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>
          ) : null}
          <form onSubmit={generate} className="grid gap-4 md:grid-cols-2">
            <label className="block">
              <span className="mb-1 block text-sm font-medium text-gray-700">Proyek</span>
              <select
                required
                value={projectId}
                onChange={(e) => setProjectId(e.target.value)}
                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              >
                <option value="">Pilih proyek...</option>
                {projects.map((project) => (
                  <option key={project.id} value={project.id}>
                    {project.name}
                  </option>
                ))}
              </select>
            </label>
            <Input
              label="Judul laporan"
              required
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="Laporan Bulanan - Agustus 2026"
            />
            <Input label="Dari tanggal" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
            <Input label="Sampai tanggal" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
            <div className="md:col-span-2">
              <Button type="submit" disabled={generating}>
                {generating ? "Memproses..." : "Generate (PDF)"}
              </Button>
            </div>
          </form>
        </Card>
      ) : null}

      {loading ? (
        <Spinner />
      ) : reports && reports.length > 0 ? (
        <Card>
          <div className="divide-y divide-gray-100">
            {reports.map((report) => (
              <div key={report.id} className="flex items-center justify-between gap-4 py-3">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-gray-800">{report.title}</p>
                  <p className="text-xs text-gray-400">
                    {report.project?.name ?? "-"} • {new Date(report.created_at).toLocaleString("id-ID")}
                  </p>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                  {statusBadge(report.status)}
                  {report.status === "ready" && report.download_url ? (
                    <Button variant="secondary" onClick={() => download(report)}>
                      Unduh
                    </Button>
                  ) : null}
                </div>
              </div>
            ))}
          </div>
        </Card>
      ) : (
        <EmptyState message="Belum ada laporan. Buat laporan pertama untuk proyek Anda." />
      )}
    </div>
  );
}
