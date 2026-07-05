<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_term_sections', function (Blueprint $table) {
            $table->string('document_type')->default('terms')->after('id');
            $table->index(['document_type', 'is_active', 'sort_order']);
        });

        DB::table('legal_term_sections')->update(['document_type' => 'terms']);

        DB::table('legal_term_sections')->insert([
            $this->section(10, 'Dados recolhidos', '<p>Podemos recolher nome, email, telefone, dados de conta, histórico de reservas, estado de pagamento e códigos de acesso associados às reservas.</p>', 'Data collected', '<p>We may collect name, email, phone number, account data, booking history, payment status, and access codes associated with bookings.</p>'),
            $this->section(20, 'Finalidades', '<p>Os dados são utilizados para criar e gerir contas, processar reservas, enviar confirmações, disponibilizar códigos de acesso, prestar suporte e manter registos operacionais.</p>', 'Purposes', '<p>Data is used to create and manage accounts, process bookings, send confirmations, provide access codes, support customers, and maintain operational records.</p>'),
            $this->section(30, 'Conservação e segurança', '<p>Os dados são guardados pelo tempo necessário à prestação do serviço e cumprimento de obrigações legais ou operacionais. São aplicadas medidas técnicas adequadas para limitar acesso não autorizado.</p>', 'Retention and security', '<p>Data is kept for the period required to provide the service and meet legal or operational obligations. Appropriate technical measures are applied to limit unauthorized access.</p>'),
            $this->section(40, 'Direitos do titular', '<p>O cliente pode solicitar acesso, retificação, limitação ou eliminação dos seus dados, nos termos da legislação aplicável, sem prejuízo de dados que tenham de ser conservados por obrigação legal.</p>', 'Data subject rights', '<p>Customers may request access, correction, restriction, or deletion of their data under applicable law, without prejudice to data that must be retained due to legal obligations.</p>'),
            $this->section(50, 'Contacto de privacidade', '<p>Pedidos relacionados com dados pessoais devem ser enviados para a equipa Dream Gym através dos contactos disponibilizados no site.</p>', 'Privacy contact', '<p>Requests related to personal data should be sent to the Dream Gym team through the contacts provided on the website.</p>'),
        ]);
    }

    public function down(): void
    {
        DB::table('legal_term_sections')
            ->where('document_type', 'privacy')
            ->delete();

        Schema::table('legal_term_sections', function (Blueprint $table) {
            $table->dropIndex(['document_type', 'is_active', 'sort_order']);
            $table->dropColumn('document_type');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function section(int $order, string $titlePt, string $bodyPt, string $titleEn, string $bodyEn): array
    {
        return [
            'document_type' => 'privacy',
            'title_pt' => $titlePt,
            'body_pt' => $bodyPt,
            'title_en' => $titleEn,
            'body_en' => $bodyEn,
            'sort_order' => $order,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
};
