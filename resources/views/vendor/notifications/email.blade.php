@component('mail::message')
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    switch ($level) {
        case 'success':
        case 'error':
            $color = $level;
            break;
        default:
            $color = 'primary';
    }
?>
@component('mail::button', ['url' => $actionUrl, 'color' => $color])
{{ $actionText }}
@endcomponent
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
{{ config('app.name') }}
@endif


{{-- Subcopy --}}
@isset($actionText)
@slot('subcopy')
<table>
    <tr>
        <td valign="top" width="70%">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="text-align: left; padding-right: 10px;">
                        <h3>A propos</h3>
                        <p>Numidev est une société de développement informatique spécialisé dans la création d'applications web et mobile, logiciels, sites web sur mesure, maintenance & hébergement. Nous accompagnons les entreprises dans leur transformation digitale et numérique. Vous avez une idée ou un projet numérique ? Contactez-nous pour échanger.</p>
                    </td>
                </tr>
            </table>
        </td>
        <td valign="top" width="30%" style="padding-top: 20px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="text-align: left; padding-left: 5px; padding-right: 5px;">
                        <h3>Contact</h3>
                        <ul>
                            <li><p>12 Rue du Val de Mayenne, 53000 Laval<p></li>
                            <li style="margin-top: 15px"><span style="font-size: 0.8em">02 43 53 86 97</span></a></li>
                        </ul>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endslot
@endisset
@endcomponent
