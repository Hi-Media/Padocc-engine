{****** ALL JS ******}

    {****** HOME ******}
    {if $sPageName == 'home'}
        {combine compress=true concat=true}
            <script type="text/javascript" src="/js/lib/jquery.cookie.js"></script>
            <script type="text/javascript" src="/js/lib/jquery.carousel.min.js"></script>
            <script type="text/javascript" src="/js/lib/jquery.outside-events.min.js"></script>
            <script type="text/javascript" src="/js/lib/jquery.tw.autocomplete.js"></script>
        {/combine}
        {combine compress=true concat=true}
            <script type="text/javascript" src="/js/lib/jquery.outside-events.min.js"></script>
            <script type="text/javascript" src="/js/lib/jquery.tw.autocomplete.js"></script>
        {/combine}
    {/if}
