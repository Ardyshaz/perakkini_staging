<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@perakkini.test'],
            [
                'name' => 'Perak Kini Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $politics = Category::firstOrCreate(
            ['slug' => 'politik'],
            ['name' => 'Politik']
        );

        $community = Category::firstOrCreate(
            ['slug' => 'masyarakat'],
            ['name' => 'Masyarakat']
        );

        $tag = Tag::firstOrCreate(
            ['slug' => 'perak'],
            ['name' => 'Perak']
        );

        $post = Post::firstOrCreate(
            ['slug' => 'selamat-datang-ke-perak-kini'],
            [
                'user_id' => $admin->id,
                'category_id' => $politics->id,
                'title' => 'Selamat Datang Ke Perak Kini',
                'excerpt' => 'Platform berita Laravel yang ringan, pantas dan mudah diurus.',
                'content' => '<p>Ini ialah artikel contoh untuk menguji paparan portal berita Perak Kini.</p>',
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now(),
            ]
        );

        $post->tags()->syncWithoutDetaching([$tag->id]);

        Post::firstOrCreate(
            ['slug' => 'komuniti-perak-terus-aktif'],
            [
                'user_id' => $admin->id,
                'category_id' => $community->id,
                'title' => 'Komuniti Perak Terus Aktif',
                'excerpt' => 'Contoh berita masyarakat untuk grid dan sidebar.',
                'content' => '<p>Kandungan ini boleh diganti selepas migrasi WordPress selesai.</p>',
                'status' => 'published',
                'published_at' => now()->subHour(),
            ]
        );
    }
}
