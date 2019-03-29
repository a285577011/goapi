<?php

/* @var $this yii\web\View */

$this->title = '咕啦体育-彩店后台';
//$this->params["admin_name"] = $admin_name;

$topTemp = '<div class="mytopnav" data-url="{auth_url}">{auth_name}</div>';
$leftTempli_1 = '<li class="tpl-left-nav-item">
                <a href="javascript:;" class="nav-link" data-url="{auth_url}">
                    <i class="am-icon-wpforms"></i>
                    <span class="controllername">{auth_name}</span>
                </a>
            </li>';
$leftTempli_2 = '<li class="tpl-left-nav-item">
                        <a href="javascript:;" class="nav-link tpl-left-nav-link-list" data-url="{auth_url}">
                            <i class="am-icon-wpforms"></i>
                            <span class="controllername">{auth_name}</span>
                            <i class="am-icon-angle-right tpl-left-nav-more-ico am-fr am-margin-right tpl-left-nav-more-ico-right tpl-left-nav-more-ico-rotate"></i>
                        </a>
                        <ul class="tpl-left-nav-sub-menu" style="display: block;">
                            <li>{html_leftnav_a}</li>
                        </ul>
                    </li>';
$leftTempli_a = '<a href="javascript:;" data-url="{auth_url}">
                                    <i class="am-icon-angle-right"></i>
                                    <span class="controllername">{auth_name}</span>
                                </a>';
$html_topnav = "";
$html_leftnav = "";
foreach ($menus as $value) {
    $html_topnav.=strtr($topTemp, [
        '{auth_url}' => $value['auth_url'],
        '{auth_name}' => $value['auth_name']
    ]);
    if (is_array($value["childrens"]) && count($value["childrens"]) > 0) {

        $html_leftnav.='<ul class="tpl-left-nav-menu" data-url="' . $value['auth_url'] . '" style="display:none;">';
        foreach ($value["childrens"] as $value1) {
            if (is_array($value1["childrens"]) && count($value1["childrens"]) > 0) {
                $html_leftnav_a = "";
                foreach ($value1["childrens"] as $value2) {
                    $html_leftnav_a .=strtr($leftTempli_a, [
                        '{auth_url}' => $value2['auth_url'],
                        '{auth_name}' => $value2['auth_name']
                    ]);
                }
                $html_leftnav.=strtr($leftTempli_2, [
                    '{auth_url}' => $value1['auth_url'],
                    '{auth_name}' => $value1['auth_name'],
                    '{html_leftnav_a}' => $html_leftnav_a
                ]);
            } else {
                $html_leftnav.=strtr($leftTempli_1, [
                    '{auth_url}' => $value1['auth_url'],
                    '{auth_name}' => $value1['auth_name']
                ]);
            }
        }
        $html_leftnav.='</ul>';
    }
}
$this->params["html_topnav"] = $html_topnav;
$this->params["html_leftnav"] = $html_leftnav;
?>
