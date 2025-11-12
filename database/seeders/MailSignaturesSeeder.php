<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MailSignature;

class MailSignaturesSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nombre'   => 'Luis F. Werner Medina — Administrador',
                'html'     => <<<'HTML'
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-family:Arial,Helvetica,sans-serif;">
                <tr>
                    <td align="center" style="padding-top:12px;color:#274b7a;">
                    <div style="font-size:18px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">LUIS F. WERNER MEDINA</div>
                    <div style="font-size:14px;margin-top:6px;">Administrador</div>
                    <div style="font-size:14px;margin-top:2px;">Cuarto Tribunal de Juicio Oral en lo Penal de Santiago</div>
                    </td>
                </tr>
                </table>
                HTML,
                'activo'   => true,
                'orden'    => 1,
                'from_env' => 'MAIL_FROM_LUIS',
                'mailer'   => 'luis',
            ],
            [
                'nombre'   => 'Eduardo Fredes Tapia — Jefe de Servicio',
                'html'     => <<<'HTML'
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-family:Arial,Helvetica,sans-serif;">
                <tr>
                    <td align="center" style="padding-top:12px;color:#274b7a;">
                    <div style="font-size:18px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">EDUARDO FREDES TAPIA</div>
                    <div style="font-size:14px;margin-top:6px;">Jefe de Servicios</div>
                    <div style="font-size:14px;margin-top:2px;">Cuarto Tribunal de Juicio Oral en lo Penal de Santiago</div>
                    </td>
                </tr>
                </table>
                HTML,
                'activo'   => true,
                'orden'    => 2,
                'from_env' => 'MAIL_FROM_EDUARDO',
                'mailer'   => 'eduardo',
            ],
            [
                'nombre'   => 'Eduardo Fredes Tapia — Administrador (S)',
                'html'     => <<<'HTML'
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-family:Arial,Helvetica,sans-serif%;">
                <tr>
                    <td align="center" style="padding-top:12px;color:#274b7a;">
                    <div style="font-size:18px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">EDUARDO FREDES TAPIA</div>
                    <div style="font-size:14px;margin-top:6px;">Administrador (S)</div>
                    <div style="font-size:14px;margin-top:2px;">Cuarto Tribunal de Juicio Oral en lo Penal de Santiago</div>
                    </td>
                </tr>
                </table>
                HTML,
                'activo'   => true,
                'orden'    => 3,
                'from_env' => 'MAIL_FROM_EDUARDO',
                'mailer'   => 'eduardo',
            ],
            [
                'nombre'   => 'Amelia Muñoz Molina — Administradora (S)',
                'html'     => <<<'HTML'
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-family:Arial,Helvetica,sans-serif;">
                <tr>
                    <td align="left" style="padding-top:8px;color:#274b7a;font-size:14px;">Atentamente,</td>
                </tr>
                <tr>
                    <td align="center" style="padding-top:6px;color:#274b7a;">
                    <div style="font-size:18px;font-weight:700;">Amelia Muñoz Molina</div>
                    <div style="font-size:14px;margin-top:6px;">Administradora (S)</div>
                    <div style="font-size:14px;margin-top:2px;">Cuarto Tribunal de Juicio Oral en lo Penal de Santiago</div>
                    </td>
                </tr>
                </table>
                HTML,
                'activo'   => true,
                'orden'    => 4,
                'from_env' => 'MAIL_FROM_AMELIA_ADMIN',
                'mailer'   => 'amelia_admin',
            ],
            [
                'nombre'   => 'Amelia Muñoz Molina — Jefe de Unidad 4° TOP',
                'html'     => <<<'HTML'
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-family:Arial,Helvetica,sans-serif;">
                <tr>
                    <td align="left" style="padding-top:8px;color:#274b7a;font-size:14px;">Atentamente,</td>
                </tr>
                <tr>
                    <td align="center" style="padding-top:6px;color:#274b7a;">
                    <div style="font-size:18px;font-weight:700;">Amelia Muñoz Molina</div>
                    <div style="font-size:14px;margin-top:6px;">Jefe de Unidad&nbsp; 4° TOP</div>
                    <div style="font-size:14px;margin-top:2px;">Cuarto Tribunal de Juicio Oral en lo Penal de Santiago</div>
                    </td>
                </tr>
                </table>
                HTML,
                'activo'   => true,
                'orden'    => 5,
                'from_env' => 'MAIL_FROM_AMELIA_JEFE',
                'mailer'   => 'amelia_jefe',
            ],
        ];

        foreach ($data as $row) {
            MailSignature::updateOrCreate(
                ['nombre' => $row['nombre']],
                $row
            );
        }
    }
}
