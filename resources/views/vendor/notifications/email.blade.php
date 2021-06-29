@component('mail::message')
{{-- Greeting --}}
<div style="text-align: center; padding: 25px">
    <a href="https://numidev.fr"><img src="{{ asset('images/logo/logo.png') }}" alt="Plannigo Logo" width="100" height="auto"></a>
</div>
@if (! empty($greeting))
 {{ $greeting }}
@else
@if ($level === 'error')
 @lang('Whoops!')
@else
 @lang('Hello!')
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
                        <h3>À propos</h3>
                        <p>Plannigo est une application web et mobile qui vous permet de gérer votre société, vos salariés ainsi que vos projets. Vous êtes intéressé ?  Contactez-nous pour échanger.</p>
                    </td> 
                </tr>
            </table>
        </td>
        <td valign="top" width="30%" style="padding-top: 20px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="text-align: left; padding-left: 5px; padding-right: 5px;">
                        <h3>Contact</h3>
                        <p>Batiment H - rue Louis de Broglie, 53810 Changé<p>
                        <p style="font-size: 0.8em; margin-top: -15px">06.43.94.43.77</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endslot
@endisset
@endcomponent
