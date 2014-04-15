<?php

?>
<table id="dashboard"><tr>
    <td class="dash_items">
        <ul>
            <li>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>settings/"><div class="dash-icon settings"></div><p>تنظیمات سامانه</p></a>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>plugins/"><div class="dash-icon plugins"></div><p>افزونه ها</p></a>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>departments/"><div class="dash-icon departments"></div><p>بخش ها</p></a>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>security/users/"><div class="dash-icon users"></div><p>کاربران</p></a>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>security/groups/"><div class="dash-icon groups"></div><p>گروه های کاربری</p></a>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>security/rules/"><div class="dash-icon rules"></div><p>مجوز ها</p></a>
            </li>
            <li>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>contents/edit/"><div class="dash-icon content-write"></div><p>محتوای جدید</p></a>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>contents/list/"><div class="dash-icon content-list"></div><p>لیست محتوا ها</p></a>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>microblog/edit/"><div class="dash-icon article-write"></div><p>مطلب جدید</p></a>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>microblog/list/"><div class="dash-icon article-list"></div><p>لیست مطالب</p></a>
                <a class="gxui-inline-block" href="<?php echo $Admin->linkTo(''); ?>menu/list/"><div class="dash-icon menu-list"></div><p>مدیریت منو ها</p></a>
            </li>
        </ul>
    </td>
</tr></table>