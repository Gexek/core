<table id="gxal">
    <tr><td align="center">
        <form method="post" action="{%login_url:dontwrap%}" class="ui-corner-all">
            <p class="gx-logo">
                <span class="gxali"></span>
                <span class="c">G</span>e<span class="c">x</span>e<span class="c">k</span>
                <sup class="v">[smart:gx_version]</sup>
            </p>
            <p class="login-to"><span class="gxali"></span>[i18n:login_to] [smart:Utils\URL::domain()]</p>
            <p>
                <input name="[smart:$Firewall->username]" id="GX_USER" type="text" />
                <input name="[smart:$Firewall->password]" id="GX_PASS" type="password" />
                <input type="submit" value="[i18n:login]" />
            </p>
            <p class="message"><h3>{%login_message:dontwrap%}</h3></p>
        </form>
    </td></tr>
</table>