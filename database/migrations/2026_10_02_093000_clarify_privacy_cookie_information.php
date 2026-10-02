<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only amend the existing cookies section; preserve the client's other legal copy.
        DB::table('legal_term_sections')
            ->where('document_type', 'privacy')
            ->where('title_pt', '10. Cookies')
            ->update([
                'body_pt' => <<<'HTML'
<p><strong>10.1.</strong> O website utiliza cookies próprios necessários ao funcionamento da plataforma: autenticação, manutenção da sessão, idioma escolhido durante a sessão e proteção dos formulários contra pedidos fraudulentos.</p>
<p><strong>10.2.</strong> O cookie dream_gym_session identifica a sessão e o cookie XSRF-TOKEN protege os formulários. Ambos têm uma duração de 2 horas, renovada com a utilização do site. Após iniciar sessão, pode também ser criado um cookie de autenticação persistente, com nome iniciado por remember_web_, para manter a conta autenticada neste navegador durante um máximo de 400 dias. Terminar sessão remove este cookie de autenticação.</p>
<p><strong>10.3.</strong> Na configuração atual, o website não utiliza cookies de publicidade, de analítica ou de rastreamento entre websites. Os cookies estritamente necessários à prestação do serviço solicitado não dependem de consentimento prévio, pelo que não é apresentado um pedido de aceitação destes cookies.</p>
<p><strong>10.4.</strong> O utilizador pode eliminar ou bloquear cookies nas definições do navegador. O bloqueio dos cookies necessários pode impedir o início de sessão, o envio de formulários ou a conclusão de reservas e pagamentos. Em dispositivos partilhados, recomenda-se terminar sessão após a utilização.</p>
<p><strong>10.5.</strong> Esta secção contém a informação sobre cookies do Dream Gym Private, não sendo necessário consultar uma política separada. Se forem introduzidos cookies não essenciais, esta informação será atualizada e será disponibilizada uma forma de aceitar, rejeitar e retirar o consentimento antes de esses cookies serem utilizados. Para esclarecimentos, contacte <a href="mailto:info@dreamgym.pt">info@dreamgym.pt</a>.</p>
HTML,
                'body_en' => <<<'HTML'
<p><strong>10.1.</strong> The website uses first-party cookies needed to operate the platform: authentication, session management, the language selected during the session and protection of forms against fraudulent requests.</p>
<p><strong>10.2.</strong> The dream_gym_session cookie identifies the session and XSRF-TOKEN protects forms. Both last for 2 hours, renewed as the website is used. After signing in, a persistent authentication cookie with a name starting with remember_web_ may also be created to keep the account signed in on this browser for up to 400 days. Signing out removes this authentication cookie.</p>
<p><strong>10.3.</strong> In its current configuration, the website does not use advertising, analytics or cross-site tracking cookies. Cookies strictly necessary to provide the requested service do not require prior consent, so no acceptance request is displayed for these cookies.</p>
<p><strong>10.4.</strong> Users can delete or block cookies in their browser settings. Blocking necessary cookies may prevent signing in, submitting forms or completing bookings and payments. Always sign out after using a shared device.</p>
<p><strong>10.5.</strong> This section provides Dream Gym Private's cookie information; there is no separate cookie policy to consult. If non-essential cookies are introduced, this information will be updated and a way to accept, reject and withdraw consent will be provided before those cookies are used. For questions, contact <a href="mailto:info@dreamgym.pt">info@dreamgym.pt</a>.</p>
HTML,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Content corrections are retained on rollback to avoid restoring inaccurate disclosures.
    }
};
