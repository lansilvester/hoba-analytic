"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Button, Card, EmptyState, PageHeader, Spinner } from "@/components/ui";
import { api, ApiError } from "@/lib/api";
import type { Project } from "@/types";

export default function ProjectsPage() {
  const [projects, setProjects] = useState<Project[] | null>(null);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    api
      .get<{ data: Project[] }>("/projects")
      .then((res) => setProjects(res.data))
      .catch(() => setProjects([]))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    const timer = setTimeout(load, 0);
    return () => clearTimeout(timer);
  }, []);

  const remove = async (project: Project) => {
    if (!confirm(`Hapus proyek "${project.name}" beserta semua artikelnya?`)) return;
    try {
      await api.del(`/projects/${project.id}`);
      load();
    } catch (err) {
      alert(err instanceof ApiError ? err.message : "Gagal menghapus proyek");
    }
  };

  return (
    <div>
      <PageHeader
        title="Proyek"
        subtitle="Proyek monitoring dan keyword-nya"
        action={
          <Link href="/projects/new">
            <Button>+ Proyek Baru</Button>
          </Link>
        }
      />

      {loading ? (
        <Spinner />
      ) : projects && projects.length > 0 ? (
        <div className="grid gap-4 lg:grid-cols-2">
          {projects.map((project) => (
            <Card key={project.id}>
              <div className="flex items-start justify-between">
                <div>
                  <Link href={`/articles?project_id=${project.id}`} className="text-base font-semibold text-gray-900 hover:text-indigo-600">
                    {project.name}
                  </Link>
                  <p className="mt-0.5 text-sm text-gray-500">{project.description || "-"}</p>
                </div>
                <button onClick={() => remove(project)} className="text-sm text-red-500 hover:underline">
                  Hapus
                </button>
              </div>

              <div className="mt-3 flex flex-wrap gap-1.5">
                {project.keywords.map((keyword) => (
                  <span key={keyword} className="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                    {keyword}
                  </span>
                ))}
              </div>

              <div className="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-sm">
                <span className="text-gray-500">
                  {project.sources.map((s) => s.name).join(", ") || "Semua sumber"}
                </span>
                <span className="font-medium text-gray-700">{project.article_count} artikel</span>
              </div>
            </Card>
          ))}
        </div>
      ) : (
        <EmptyState message="Belum ada proyek. Buat proyek pertama Anda." />
      )}
    </div>
  );
}
