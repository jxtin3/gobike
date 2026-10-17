<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('title', 150);
            $table->string('last_updated', 50)->nullable();
            $table->json('sections')->nullable();
            $table->timestamps();
        });

        $now = now();

        $privacySections = [
            [
                'title' => '1. Information We Collect',
                'content' => 'We collect information that you provide directly to us when you volunteer, donate, or contact us. This may include your name, email address, phone number, and any other information you choose to provide.',
            ],
            [
                'title' => '2. How We Use Your Information',
                'content' => 'We use the information we collect to operate our programs, communicate with you, process donations, and improve our community services across Pangasinan.',
            ],
            [
                'title' => '3. Sharing of Information',
                'content' => 'We do not share your personal information with third parties except as necessary to provide our services, coordinate with our government partners (such as DILG or DOH), or comply with the law.',
            ],
            [
                'title' => '4. Security',
                'content' => 'We take reasonable measures to help protect your personal information from loss, theft, misuse, and unauthorized access, disclosure, alteration, and destruction.',
            ],
            [
                'title' => '5. Contact Us',
                'content' => 'If you have any questions about this Privacy Policy, please contact our community desk through the contact page.',
            ],
        ];

        $termsSections = [
            [
                'title' => '1. Acceptance of Terms',
                'content' => 'By accessing and using the Go Bike website and participating in our programs, you accept and agree to be bound by the terms and provisions of this agreement.',
            ],
            [
                'title' => '2. Volunteer Conduct',
                'content' => 'Volunteers and community responders are expected to maintain the highest standards of respect, safety, and community service. Go Bike reserves the right to revoke volunteer status for any behavior that violates our community guidelines.',
            ],
            [
                'title' => '3. Use of Site',
                'content' => 'You may use our site for lawful purposes and in accordance with these Terms. You agree not to use the site in any way that violates any applicable national or local law or regulation.',
            ],
            [
                'title' => '4. Intellectual Property',
                'content' => 'The content, features, and functionality of this website are owned by Go Bike and are protected by copyright, trademark, and other intellectual property laws.',
            ],
            [
                'title' => '5. Changes to Terms',
                'content' => 'We reserve the right to modify these terms at any time. Your continued use of the site or participation in our programs constitutes acceptance of those changes.',
            ],
        ];

        DB::table('legal_documents')->insert([
            [
                'key' => 'privacy',
                'title' => 'Privacy Policy',
                'last_updated' => date('F d, Y'),
                'sections' => json_encode($privacySections),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'terms',
                'title' => 'Terms & Conditions',
                'last_updated' => date('F d, Y'),
                'sections' => json_encode($termsSections),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
