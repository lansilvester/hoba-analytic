<?php

namespace Database\Seeders;

use App\Models\Analysis;
use App\Models\Article;
use App\Models\Keyword;
use App\Models\Project;
use App\Models\Role;
use App\Models\Source;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect(['admin', 'editor', 'viewer'])->mapWithKeys(fn (string $name) => [
            $name => Role::create([
                'name' => $name,
                'permissions' => $this->rolePermissions($name),
            ]),
        ]);

        $sources = collect([
            ['name' => 'Kompas', 'base_url' => 'https://www.kompas.com', 'type' => 'news'],
            ['name' => 'Detik', 'base_url' => 'https://www.detik.com', 'type' => 'news'],
            ['name' => 'Tempo', 'base_url' => 'https://www.tempo.co', 'type' => 'news'],
            ['name' => 'Antara', 'base_url' => 'https://www.antaranews.com', 'type' => 'news'],
        ])->map(fn (array $data) => Source::create($data));

        $tenant = Tenant::create(['name' => 'Pixel Joy Demo', 'slug' => 'pixel-joy-demo']);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'role_id' => $roles['admin']->id,
            'name' => 'Admin Demo',
            'email' => 'admin@hoba.test',
            'password' => 'password',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'role_id' => $roles['editor']->id,
            'name' => 'Editor Demo',
            'email' => 'editor@hoba.test',
            'password' => 'password',
        ]);

        $project = Project::create([
            'tenant_id' => $tenant->id,
            'name' => 'Brand Monitoring 2026',
            'description' => 'Pantau brand utama',
        ]);

        collect(['Pixel Joy', 'media monitoring', 'reputasi'])->each(
            fn (string $keyword) => Keyword::create(['project_id' => $project->id, 'keyword' => $keyword]),
        );

        $project->sources()->sync($sources->pluck('id'));

        $sampleArticles = [
            ['Kompas optimistis ekonomi Indonesia tumbuh di 2026', 'positive'],
            ['Pemerintah siapkan insentif baru untuk industri kreatif', 'positive'],
            ['Penjualan ritel turun di tengah ketidakpastian global', 'negative'],
            ['Inflasi tahunan diproyeksikan tetap terkendali', 'neutral'],
            ['Detik mencatat lonjakan investasi teknologi tahun ini', 'positive'],
            ['Harga pangan bergejolak di sejumlah daerah', 'negative'],
        ];

        foreach ($sampleArticles as $index => [$title, $sentiment]) {
            $article = Article::create([
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'source_id' => $sources->get($index % 4)->id,
                'title' => $title,
                'url' => 'https://example.com/artikel-'.($index + 1),
                'content' => 'Konten artikel lengkap untuk "'.$title.'".',
                'published_at' => now()->subDays(6 - $index),
            ]);

            Analysis::create([
                'article_id' => $article->id,
                'sentiment' => $sentiment,
                'confidence' => 0.85 + ($index / 100),
                'topic' => 'ekonomi',
                'entities' => [
                    ['type' => 'GPE', 'text' => 'Indonesia'],
                ],
                'analyzed_at' => now(),
            ]);
        }
    }

    protected function rolePermissions(string $role): array
    {
        return match ($role) {
            'admin' => ['projects.read', 'projects.write', 'projects.delete', 'articles.read', 'reports.write', 'users.manage'],
            'editor' => ['projects.read', 'projects.write', 'articles.read', 'reports.write'],
            default => ['projects.read', 'articles.read'],
        };
    }
}
