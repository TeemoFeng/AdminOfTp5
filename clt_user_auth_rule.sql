/*
Navicat MySQL Data Transfer

Source Server         : local
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : cltdemo_db

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2019-03-21 23:32:31
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for clt_user_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `clt_user_auth_rule`;
CREATE TABLE `clt_user_auth_rule` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `href` varchar(50) NOT NULL DEFAULT '' COMMENT '路径',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态',
  `type` tinyint(2) NOT NULL DEFAULT '1',
  `authopen` tinyint(2) NOT NULL,
  `icon` varchar(20) DEFAULT NULL,
  `condition` varchar(100) DEFAULT '',
  `pid` int(5) NOT NULL DEFAULT '0',
  `sort` int(11) DEFAULT NULL,
  `addtime` int(11) NOT NULL DEFAULT '0',
  `zt` int(1) DEFAULT NULL,
  `menustatus` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of clt_user_auth_rule
-- ----------------------------
INSERT INTO `clt_user_auth_rule` VALUES ('1', 'User', '会员管理', '1', '1', '0', 'icon-user', '', '0', '10', '1553177450', null, '1');
INSERT INTO `clt_user_auth_rule` VALUES ('2', 'User/index', '会员列表', '1', '1', '0', '', '', '1', '50', '1553177811', null, '1');
INSERT INTO `clt_user_auth_rule` VALUES ('3', 'User/notActivate', '未激活列表', '1', '1', '0', '', '', '1', '50', '1553178066', null, '1');
INSERT INTO `clt_user_auth_rule` VALUES ('4', 'user/userTree', '直推架构树', '1', '1', '0', '', '', '1', '50', '1553178138', null, '1');
INSERT INTO `clt_user_auth_rule` VALUES ('5', 'User/originReset', '原点复投', '1', '1', '0', '', '', '1', '50', '1553178210', null, '1');
INSERT INTO `clt_user_auth_rule` VALUES ('7', 'User/withdraw', '撤回复投', '1', '1', '0', '', '', '1', '50', '1553178755', null, '1');
