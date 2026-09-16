<div style="font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #3c4043; background-color: #f8f9fa; padding: 40px 20px; border-radius: 10px;">
 
    <div style="border: 1px solid #dadce0; border-radius: 8px; padding: 32px; background-color: #ffffff; text-align: left; box-shadow: 0 2px 4px rgba(0,0,0,0.07);">
        @if($showTitle)
            <div style="font-size: 20px; line-height: 28px; font-weight: 500; color: #202124; margin-bottom: 16px;">
                {{ $title }}
            </div>
        @endif

        <div style="font-size: 15px; color: #5f6368; margin-bottom: 24px;">
            Hello {{ $name }},
        </div>

        @if($showSubtitle)
            <div style="font-size: 15px; line-height: 24px; margin-bottom: 20px;">
                {{ $subtitle }}
            </div>

            <div style="border-bottom: 1px solid #eeeeee; margin-bottom: 20px;"></div>
        @endif

        <div style="font-size: 15px; line-height: 1.6; color: #3c4043; margin-bottom: 24px;">
            {!! $body !!}
        </div>
    </div>

    <div style="font-size: 12px; line-height: 18px; color: #70757a; text-align: center; margin-top: 24px;">
        You have received this email securely from <strong>{{ $code }}</strong>, via Cilantro school management system.
        <br>
        © {{ $year }} Pryme Industries, Cameroon
        <br>
        <a href="https://pryme-industries.com/cilantro" style="color: #1a73e8; text-decoration: none;">https://pryme-industries.com/cilantro</a>
    </div>

</div>