<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Card designs managed from the dashboard. `slug` is what gift_cards.design stores.
 * Seeded with the seven designs that were hard-coded in front-end src/data/gifts.js.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_designs', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('occasion', 40)->nullable();               // birthday, eid, ramadan, general, ...
            $table->text('background');                               // CSS background (gradient)
            $table->string('foreground', 20)->default('#E8BE6C');
            $table->string('icon', 8)->nullable();
            $table->string('image_path')->nullable();                 // optional uploaded artwork
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $designs = [
            ['lux-dark', 'ملكي أسود مذهب', 'Royal black & gold', 'general', 'radial-gradient(circle at 20% 12%,rgba(232,190,108,.24),transparent 30%),linear-gradient(150deg,#1D1810,#070604 72%)', '#E8BE6C', '◆'],
            ['gold-leaf', 'ختم الذهب الفاخر', 'Gold leaf seal', 'general', 'linear-gradient(150deg,#FFF4D8,#D4AF5E 54%,#7C5519)', '#1B140D', '✦'],
            ['emerald', 'زمرد النخبة', 'Elite emerald', 'general', 'radial-gradient(circle at 80% 8%,rgba(255,255,255,.18),transparent 28%),linear-gradient(150deg,#082118,#176A50 58%,#05110D)', '#DDF7E8', '◇'],
            ['birthday', 'هدية عيد ميلاد', 'Birthday gift', 'birthday', 'radial-gradient(circle at 20% 18%,rgba(255,255,255,.28),transparent 30%),linear-gradient(150deg,#4B102E,#B94069 56%,#F2B8CF)', '#FFFFFF', '★'],
            ['eid-adha', 'هدية عيد الأضحى', 'Eid al-Adha gift', 'eid', 'radial-gradient(circle at 85% 12%,rgba(255,255,255,.22),transparent 28%),linear-gradient(150deg,#092B25,#0F6B58 52%,#D8B45B)', '#FFF7DA', '☾'],
            ['christmas', 'هدية الكريسماس', 'Christmas gift', 'christmas', 'linear-gradient(150deg,#0D2B1F,#146B3A 52%,#B41F2A)', '#FFFFFF', '✶'],
            ['ramadan', 'هدية رمضان والعيد', 'Ramadan & Eid gift', 'ramadan', 'radial-gradient(circle at 20% 10%,rgba(232,190,108,.26),transparent 32%),linear-gradient(150deg,#11132C,#26316B 55%,#C99B3B)', '#F8E7B0', '☾'],
        ];

        foreach ($designs as $i => [$slug, $ar, $en, $occasion, $bg, $fg, $icon]) {
            DB::table('gift_designs')->insert([
                'slug' => $slug, 'name_ar' => $ar, 'name_en' => $en, 'occasion' => $occasion,
                'background' => $bg, 'foreground' => $fg, 'icon' => $icon, 'is_active' => true,
                'sort_order' => $i, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_designs');
    }
};
