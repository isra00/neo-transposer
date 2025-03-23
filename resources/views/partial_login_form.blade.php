{{-- Partial variables: $error_msg, $redirect --}}

<form method="post" action="{{ route('login', ['redirect' => $redirect ?? '', 'locale' => app()->getLocale()]) }}" class="login-form" id="login-form">
    @csrf
    <div class="field block full-width">
        <label for="email">@lang('Please, type your e-mail:')</label>
        <input type="email" name="email" id="email" value="{{ request()->post('email') }}" autofocus required onkeyup="mcheck(this)" onchange="validate(this)" pattern="[a-zA-Z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?">
        <div class="field-error" id="error">{{ $error_msg ?? '' }}</div>
        <div id="mcheck"></div>
    </div>
    <div class="field block full-width">
    @if(config('nt.debug') || config('nt.disable_recaptcha'))
        <button type="submit" name="sent" class="bigbutton">@lang('Enter')</button>
    @else
        <button class="bigbutton g-recaptcha" data-sitekey="6LfXByMUAAAAAKkFOyhseUPNtuz8ZiilLUB3B5iX" data-callback="login">@lang('Enter')</button>
    @endif
    </div>
</form>

<script>
    function login(token)
    {
        document.getElementById("login-form").submit();
    }
</script>

<script src="{{ url('') }}/static/mailcheck-1.1.2.min.js"></script>

<script>

    document.addEventListener('invalid', (function () {
        return function (e) {
            e.preventDefault();
            validate(document.getElementById("email"));
            document.getElementById("email").focus();
        };
    })(), true);

    function validate(inputElement)
    {
        if (inputElement.validity.patternMismatch)
        {
            document.getElementById("error").innerHTML = "@lang('That e-mail doesn\'t look good. Please, re-type it.')"
			}
        else
        {
            document.getElementById("error").innerHTML = "";
        }
    }

    function mcheck(inputElement)
    {
        if (inputElement.validity.valid)
        {
            Mailcheck.run({
                email: inputElement.value,
                suggested: function(suggestion) {
                    validate(document.getElementById("email"));
                    document.getElementById("mcheck").style.display = "block";
                    document.getElementById("mcheck").innerHTML = "@lang('You mean') <a href='javascript:void(0)' onclick=\"document.getElementById('email').value='" + suggestion.full + "'; this.parentNode.style.display='none';\">" + suggestion.full + "</a>?";
                }
            });
        }
        return false;
    }
</script>
