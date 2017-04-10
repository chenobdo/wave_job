此模块基于Wave框架下开发 Wave地址：https://github.com/obdobriel/wavephp2

任务队列引擎是mysql数据库

表
```
CREATE TABLE `k_jobs` (
  `jid` int(11) NOT NULL AUTO_INCREMENT,
  `job` varchar(64) NOT NULL COMMENT '任务名称',
  `mark` text COMMENT '备注',
  `type` tinyint(2) DEFAULT '1' COMMENT '类型（1-正式；2-测试）',
  `priority` tinyint(4) NOT NULL DEFAULT '10' COMMENT '优先级（数字大优先级高：最高级-100；高-50；普通-10；低-0）',
  `execute_after` datetime NOT NULL COMMENT '执行时间',
  `params` text COMMENT '任务参数',
  `status` tinyint(2) NOT NULL COMMENT '状态（1-未执行；2-执行中；10-已执行；100-已过期；101-尝试多次）',
  `attempts` tinyint(1) NOT NULL DEFAULT '0' COMMENT '执行次数',
  `last_attempt_time` datetime DEFAULT NULL COMMENT '最后一次执行时间',
  `pjid` int(11) DEFAULT NULL COMMENT '周期任务ID',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`jid`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8;
```

```
CREATE TABLE `k_periodic_jobs` (
  `pjid` int(11) NOT NULL AUTO_INCREMENT,
  `pjob` varchar(64) NOT NULL COMMENT '任务名称',
  `mark` text COMMENT '备注',
  `type` tinyint(2) DEFAULT '1' COMMENT '类型（1-正式；2-测试）',
  `priority` tinyint(4) NOT NULL DEFAULT '10' COMMENT '优先级（数字大优先级高：最高级-100；高-50；普通-10；低-0）',
  `params` text COMMENT '任务参数',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态（1-可执行；10-暂停）',
  `period` varchar(16) DEFAULT NULL COMMENT '周期',
  `period_parameter` text COMMENT '周期参数',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`pjid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
```
