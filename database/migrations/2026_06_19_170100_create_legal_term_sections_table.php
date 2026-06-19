<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_term_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title_pt');
            $table->text('body_pt');
            $table->string('title_en');
            $table->text('body_en');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        DB::table('legal_term_sections')->insert([
            $this->section(10, 'Reservas', 'As reservas são efetuadas em horas e ficam sujeitas à disponibilidade apresentada no momento da confirmação.', 'Bookings', 'Bookings are made by hour and depend on availability at the time of confirmation.'),
            $this->section(20, 'Acesso à sala', 'Após confirmação do pagamento, o cliente recebe um código de 6 dígitos. O código é pessoal, não deve ser partilhado, e fica válido apenas entre 5 minutos antes e 5 minutos depois do período reservado.', 'Room access', 'After payment confirmation, the customer receives a 6-digit code. The code is personal, must not be shared, and is valid only from 5 minutes before to 5 minutes after the booked period.'),
            $this->section(30, 'Pagamentos e confirmações', 'Nesta fase de testes, o pagamento é simulado em ambiente sandbox. Em produção, a reserva só será considerada confirmada após validação do método de pagamento disponibilizado.', 'Payments and confirmations', 'At this testing stage, payment is simulated in a sandbox environment. In production, a booking will only be confirmed after validation by the available payment method.'),
            $this->section(40, 'Cancelamentos', 'O cancelamento até 24 horas antes do início da reserva devolve o crédito de sessão à conta do cliente. Após esse prazo, o crédito não é devolvido e a sessão é considerada perdida.', 'Cancellations', 'Cancellation up to 24 hours before the booking starts returns the session credit to the customer account. After that deadline, the credit is not returned and the session is considered lost.'),
            $this->section(50, 'Crianças no espaço', 'Quando o cliente indica que vai trazer crianças, declara que estas permanecem sob a sua responsabilidade durante toda a permanência no espaço.', 'Children in the space', 'When the customer indicates that they will bring children, they declare that the children remain under their responsibility throughout their stay in the space.'),
            $this->section(60, 'Utilização responsável', 'O cliente deve utilizar a sala, equipamentos e acessos de forma responsável, cumprir os horários reservados e comunicar qualquer problema detetado antes, durante ou após a utilização.', 'Responsible use', 'The customer must use the room, equipment, and access credentials responsibly, respect the booked times, and report any issue found before, during, or after use.'),
            $this->section(70, 'Contacto', 'Para questões sobre reservas, pagamentos, acesso ou utilização da plataforma, o cliente deve contactar a equipa Dream Gym através dos contactos disponibilizados no site.', 'Contact', 'For questions about bookings, payments, access, or platform use, the customer should contact the Dream Gym team through the contacts provided on the website.'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_term_sections');
    }

    /**
     * @return array<string, mixed>
     */
    private function section(int $order, string $titlePt, string $bodyPt, string $titleEn, string $bodyEn): array
    {
        return [
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
