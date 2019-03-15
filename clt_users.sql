/*
Navicat MySQL Data Transfer

Source Server         : localhost_3306
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : cltdemo_db

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2019-03-15 18:00:58
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for clt_users
-- ----------------------------
DROP TABLE IF EXISTS `clt_users`;
CREATE TABLE `clt_users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `usernum` varchar(10) NOT NULL DEFAULT '' COMMENT '用户编号',
  `email` varchar(60) NOT NULL DEFAULT '' COMMENT '邮件',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '密码',
  `safeword` varchar(32) NOT NULL DEFAULT '' COMMENT '安全密码',
  `paypwd` varchar(32) DEFAULT NULL COMMENT '支付密码',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 男 0 女',
  `birthday` int(11) NOT NULL DEFAULT '0' COMMENT '生日',
  `reg_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间',
  `active_time` int(10) NOT NULL DEFAULT '0' COMMENT '激活时间',
  `last_login` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '最后登录ip',
  `qq` varchar(20) NOT NULL DEFAULT '' COMMENT 'QQ',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号码',
  `mobile_validated` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否验证手机',
  `oauth` varchar(10) DEFAULT '' COMMENT '第三方来源 wx weibo alipay',
  `openid` varchar(100) DEFAULT NULL COMMENT '第三方唯一标示',
  `unionid` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `province` int(6) DEFAULT '0' COMMENT '省份',
  `city` int(6) DEFAULT '0' COMMENT '市区',
  `district` int(6) DEFAULT '0' COMMENT '县',
  `email_validated` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否验证电子邮箱',
  `username` varchar(50) DEFAULT NULL COMMENT '第三方返回昵称',
  `level` tinyint(1) DEFAULT '1' COMMENT '会员等级',
  `is_lock` tinyint(1) DEFAULT '0' COMMENT '是否被锁定冻结',
  `token` varchar(64) DEFAULT '' COMMENT '用于app 授权类似于session_id',
  `sign` varchar(255) DEFAULT '' COMMENT '签名',
  `status` varchar(20) DEFAULT 'hide' COMMENT '登录状态',
  `referee` varchar(50) NOT NULL DEFAULT '' COMMENT '推荐人编号',
  `single_currency` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '排单币',
  `static_gains` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '静态收益',
  `dynamic_revenue` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '动态收益',
  `identity` varchar(25) NOT NULL DEFAULT '' COMMENT '身份证号',
  `contact_person` varchar(50) NOT NULL DEFAULT '' COMMENT '接点人编号',
  `alipay_account` varchar(50) NOT NULL DEFAULT '' COMMENT '支付宝账号',
  `bank_id` smallint(3) NOT NULL DEFAULT '0' COMMENT '关联银行表id',
  `bank_user` varchar(20) NOT NULL DEFAULT '' COMMENT '开户人',
  `bank_account` varchar(25) NOT NULL DEFAULT '' COMMENT '银行卡账号',
  `bank_desc` varchar(80) NOT NULL DEFAULT '' COMMENT '开户行详细信息',
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of clt_users
-- ----------------------------
INSERT INTO `clt_users` VALUES ('1', '', '1109305987@qq.com', '', '', null, '1', '0', '1516075631', '0', '0', '', '', '44311', '0', '', null, null, '/uploads/20180613/fcb729987d8e9339bd9b2e85c85f3028.jpg', '24', '311', '2599', '0', 'chichu', '1', '0', '', '不要应为走得太远，就忘了当初为什么出发！', 'hide', '', '0.00', '0.00', '0.00', '', '', '', '0', '', '', '');
INSERT INTO `clt_users` VALUES ('2', '1234', '', 'e10adc3949ba59abbe56e057f20f883e', '123456', null, '0', '0', '0', '0', '0', '', '', '11111111111', '0', '', null, null, null, '0', '0', '0', '0', 'ywf01', '1', '0', '', '', 'hide', '', '0.00', '0.00', '0.00', '', '', '', '0', '', '', '');
INSERT INTO `clt_users` VALUES ('3', '123', '', 'e10adc3949ba59abbe56e057f20f883e', '123456', null, '0', '0', '0', '0', '0', '', '', '11111111112', '0', '', null, null, null, '0', '0', '0', '0', 'ywf02', '1', '0', '', '', 'hide', '', '0.00', '0.00', '0.00', '', '', '', '0', '', '', '');
INSERT INTO `clt_users` VALUES ('5', '111', '', 'e10adc3949ba59abbe56e057f20f883e', '123456', null, '0', '0', '1546947543', '0', '0', '', '', '11111111114', '0', '', null, null, null, '0', '0', '0', '0', 'ywfa', '1', '0', '', '', 'hide', '', '0.00', '0.00', '0.00', '', '', '', '0', '', '', '');
INSERT INTO `clt_users` VALUES ('6', '12345', '97633852@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '123456', null, '1', '0', '1546948905', '0', '0', '', '', '11111111115', '0', '', null, null, null, '2', '52', '503', '0', 'yangpingping', '1', '0', '', '', 'hide', '', '0.00', '0.00', '0.00', '', '', '', '0', '', '', '');
INSERT INTO `clt_users` VALUES ('7', 'vip2019030', '1109305987@qq.com', 'e10adc3949ba59abbe56e057f20f883e', '123456', null, '0', '0', '1552028692', '0', '0', '', '', '12345678911', '0', '', null, null, null, '0', '0', '0', '0', 'dfdfdf', '1', '0', '', '', 'hide', 'vip2019030827838', '0.00', '0.00', '0.00', '123456', '', '', '0', '', '', '');
