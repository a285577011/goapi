<?php

namespace app\modules\storeback\controllers;

use Yii;
use yii\web\Controller;

/**
 * Default controller for the `storeback` module
 */
class DefaultController extends Controller {

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex() {

        $this->layout = 'pageframe';
        $menus = [
            [
                "auth_url" => "home",
                "auth_name" => "首页",
                "childrens" => [
                     [
                        "auth_url" => "",
                        "auth_name" => "门店信息",
                        "childrens" => [
                            [
                                "auth_url" => "/api/storeback/home/index",
                                "auth_name" => "基本信息",
                                "childrens" => [
                                ]
                            ]
                        ]
                    ],
                ],
                  
            ], [
                "auth_url" => "piaowu",
                "auth_name" => "票务",
                "childrens" => [
                    [
                        "auth_url" => "",
                        "auth_name" => "出票管理",
                        "childrens" => [
                            [
                                "auth_url" => "/api/storeback/piaowu/index",
                                "auth_name" => "出票处理",
                                "childrens" => [
                                ]
                            ]
                        ]
                    ],
                    [
                        "auth_url" => "",
                        "auth_name" => "派奖管理",
                        "childrens" => [
                            [
                                "auth_url" => "/api/storeback/piaowu/play-awards",
                                "auth_name" => "派奖处理",
                                "childrens" => [
                                ]
                            ],
                            [
                                "auth_url" => "/api/storeback/piaowu/awards-records",
                                "auth_name" => "派奖记录",
                                "childrens" => [
                                ]
                            ]
                        ]
                    ]
                ]
            ],[
                "auth_url" => "/reoprt",
                "auth_name" => "报表",
                "childrens" => [
                     [
                        "auth_url" => "",
                        "auth_name" => "销售管理",
                        "childrens" => [
                            [
                                "auth_url" => "/api/storeback/report/index",
                                "auth_name" => "销售统计",
                                "childrens" => [
                                ]
                            ],
                             [
                                "auth_url" => "/api/storeback/report/sale-order-list",
                                "auth_name" => "销售明细",
                                "childrens" => [
                                ]
                            ]
                        ]
                    ],
                ]
            ],[
                "auth_url" => "financial",
                "auth_name" => "财务",
                "childrens" => [
                     [
                        "auth_url" => "",
                        "auth_name" => "财务管理",
                        "childrens" => [
                            [
                                "auth_url" => "/api/storeback/financial/index",
                                "auth_name" => "收支明细",
                                "childrens" => [
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            [
                "auth_url" => "sysAdmin",
                "auth_name" => "系统管理",
                "childrens" => [
                    [
                        "auth_url" => "",
                        "auth_name" => "用户管理",
                        "childrens" => [
                            [
                                "auth_url" => "/api/storeback/sysadmin/index",
                                "auth_name" => "店员管理",
                                "childrens" => []
                            ],
//                            [
//                                "auth_url" => "/api/storeback/sysadmin/role",
//                                "auth_name" => "角色管理",
//                                "childrens" => []
//                            ],
//                            [
//                                "auth_url" => "/api/storeback/sysadmin/auth",
//                                "auth_name" => "权限管理",
//                                "childrens" => []
//                            ]
                        ]
                    ],
//                    [
//                        "auth_url" => "",
//                        "auth_name" => "系统设置",
//                        "childrens" => [
//                            [
//                                "auth_url" => "/api/storeback/sysadmin/celve",
//                                "auth_name" => "策略管理",
//                                "childrens" => []
//                            ],
//                            [
//                                "auth_url" => "/api/storeback/sysadmin/log",
//                                "auth_name" => "日志管理",
//                                "childrens" => []
//                            ]
//                        ]
//                    ],
                ]
            ],
        ];
        return $this->render('index', ["menus" => $menus]);
    }

}
