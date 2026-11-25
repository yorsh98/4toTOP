<?php

namespace App\NotificationsV2\Application\Services;

use App\NotificationsV2\Application\Contracts\TemplateRenderer;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Support\OutlookEmailTemplates;
use Illuminate\Support\Arr;

class SimpleTemplateRenderer implements TemplateRenderer
{
    public function render(NotificationTemplate $template, array $payload): array
    {
        $subject = $this->replaceTokens($template->subject, $payload);
        $html = $this->replaceTokens($template->body_html, $payload, OutlookEmailTemplates::isOutlook($template->body_html));

        $textBody = $template->body_text;
        if (is_string($textBody) && trim($textBody) !== '') {
            $text = $this->replaceTokens($textBody, $payload);
        } else {
            $text = trim(strip_tags($html));
        }

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
        ];
    }

    private function replaceTokens(string $content, array $payload, bool $outlookHtml = false): string
    {
        $flatPayload = Arr::dot($payload);

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', function (array $matches) use ($flatPayload, $outlookHtml) {
            $key = $matches[1] ?? '';
            if (! array_key_exists($key, $flatPayload)) {
                if ($outlookHtml && str_ends_with($key, '_html')) {
                    $rawValue = $flatPayload[substr($key, 0, -5)] ?? null;

                    if (is_scalar($rawValue)) {
                        return e((string) $rawValue);
                    }
                }

                return $matches[0];
            }

            $value = $flatPayload[$key];
            if (is_scalar($value) || $value === null) {
                return (string) $value;
            }

            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $content) ?? $content;
    }
}
