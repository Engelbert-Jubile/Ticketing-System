<table role="presentation" style="width: 100%; margin: 0 0 18px; border-collapse: collapse; border-bottom: 2px solid #123b7a;">
    <tr>
        <td style="width: 180px; padding: 0 14px 8px 0; border: 0; vertical-align: middle;">
            <img src="{{ \App\Support\Branding::printLogoDataUri() }}" alt="{{ \App\Support\Branding::normalizeName(config('app.name')) }}" width="180" style="display: block;" />
        </td>
        <td style="padding: 8px 0; border: 0; background: #ffffff; vertical-align: middle;">
            <p style="margin: 0 0 6px; color: #123b7a; font-size: 16px; font-weight: bold;">{{ \App\Support\Branding::normalizeName(config('app.name')) }}</p>
            <p style="margin: 0; color: #475569; font-size: 9px; line-height: 1.5;">{{ \App\Support\Branding::TAGLINE }}</p>
        </td>
    </tr>
</table>
