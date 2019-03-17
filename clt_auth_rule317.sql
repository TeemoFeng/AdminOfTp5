/*
Navicat MySQL Data Transfer

Source Server         : local
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : cltdemo_db

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2019-03-17 23:58:21
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for clt_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `clt_auth_rule`;
CREATE TABLE `clt_auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `href` char(80) NOT NULL DEFAULT '',
  `title` char(20) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `authopen` tinyint(2) NOT NULL DEFAULT '1',
  `icon` varchar(20) DEFAULT NULL COMMENT '样式',
  `condition` char(100) DEFAULT '',
  `pid` int(5) NOT NULL DEFAULT '0' COMMENT '父栏目ID',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `zt` int(1) DEFAULT NULL,
  `menustatus` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=295 DEFAULT CHARSET=utf8 COMMENT='权限节点';

-- ----------------------------
-- Records of clt_auth_rule
-- ----------------------------
INSERT INTO `clt_auth_rule` VALUES ('1', 'System', '系统设置', '1', '1', '0', 'icon-cogs', '', '0', '0', '1446535750', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('2', 'System/system', '系统设置', '1', '1', '0', '', '', '1', '1', '1446535789', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('3', 'Database/database', '数据库管理', '1', '1', '0', 'icon-database', '', '0', '2', '1446535805', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('4', 'Database/restore', '还原数据库', '1', '1', '0', '', '', '3', '10', '1446535750', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('5', 'Database/database', '数据库备份', '1', '1', '0', '', '', '3', '1', '1446535834', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('7', 'Category', '栏目管理', '1', '1', '0', 'icon-list', '', '0', '4', '1446535875', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('9', 'Category/index', '栏目列表', '1', '1', '0', '', '', '7', '0', '1446535750', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('13', 'Category/edit', '操作-修改', '1', '1', '0', '', '', '9', '3', '1446535750', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('14', 'Category/add', '操作-添加', '1', '1', '0', '', '', '9', '0', '1446535750', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('15', 'Auth/adminList', '权限管理', '1', '1', '0', 'icon-lifebuoy', '', '0', '1', '1446535750', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('16', 'Auth/adminList', '管理员列表', '1', '1', '0', '', '', '15', '0', '1446535750', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('17', 'Auth/adminGroup', '用户组列表', '1', '1', '0', '', '', '15', '1', '1446535750', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('18', 'Auth/adminRule', '权限管理', '1', '1', '0', '', '', '15', '2', '1446535750', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('23', 'Help/soft', '软件下载', '1', '1', '0', '', '', '22', '50', '1446711421', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('27', 'Users', '会员管理', '1', '1', '0', 'icon-user', '', '0', '5', '1447231507', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('28', 'Function', '网站功能', '1', '1', '0', 'icon-cog', '', '0', '6', '1447231590', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('29', 'Users/index', '会员列表', '1', '1', '0', '', '', '27', '10', '1447232085', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('31', 'Link/index', '友情链接', '1', '1', '0', '', '', '28', '2', '1447232183', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('32', 'Link/add', '操作-添加', '1', '1', '0', '', '', '31', '1', '1447639935', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('36', 'We/we_menu', '自定义菜单', '1', '1', '0', '', '', '35', '50', '1447842477', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('38', 'Users/userGroup', '会员组', '1', '1', '0', '', '', '27', '50', '1448413248', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('39', 'We/we_menu', '自定义菜单', '1', '1', '0', '', '', '36', '50', '1448501584', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('45', 'Ad/index', '广告管理', '1', '1', '0', '', '', '28', '3', '1450314297', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('46', 'Ad/type', '广告位管理', '1', '1', '0', '', '', '28', '4', '1450314324', '1', '1');
INSERT INTO `clt_auth_rule` VALUES ('48', 'Message/index', '留言管理', '1', '1', '0', '', '', '28', '1', '1451267354', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('105', 'System/runsys', '操作-保存', '1', '1', '0', '', '', '6', '50', '1461036331', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('106', 'System/runwesys', '操作-保存', '1', '1', '0', '', '', '10', '50', '1461037680', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('107', 'System/runemail', '操作-保存', '1', '1', '0', '', '', '19', '50', '1461039346', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('108', 'Auth/ruleAdd', '操作-添加', '1', '1', '0', '', '', '18', '0', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('109', 'Auth/ruleState', '操作-状态', '1', '1', '0', '', '', '18', '5', '1461550949', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('110', 'Auth/ruleTz', '操作-验证', '1', '1', '0', '', '', '18', '6', '1461551129', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('111', 'Auth/ruleorder', '操作-排序', '1', '1', '0', '', '', '18', '7', '1461551263', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('112', 'Auth/ruleDel', '操作-删除', '1', '1', '0', '', '', '18', '4', '1461551536', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('114', 'Auth/ruleEdit', '操作-修改', '1', '1', '0', '', '', '18', '2', '1461551913', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('116', 'Auth/groupEdit', '操作-修改', '1', '1', '0', '', '', '17', '3', '1461552326', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('117', 'Auth/groupDel', '操作-删除', '1', '1', '0', '', '', '17', '30', '1461552349', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('118', 'Auth/groupAccess', '操作-权限', '1', '1', '0', '', '', '17', '40', '1461552404', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('119', 'Auth/adminAdd', '操作-添加', '1', '1', '0', '', '', '16', '0', '1461553162', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('120', 'Auth/adminEdit', '操作-修改', '1', '1', '0', '', '', '16', '2', '1461554130', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('121', 'Auth/adminDel', '操作-删除', '1', '1', '0', '', '', '16', '4', '1461554152', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('122', 'System/source_runadd', '操作-添加', '1', '1', '0', '', '', '43', '10', '1461036331', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('123', 'System/source_order', '操作-排序', '1', '1', '0', '', '', '43', '50', '1461037680', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('124', 'System/source_runedit', '操作-改存', '1', '1', '0', '', '', '43', '30', '1461039346', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('125', 'System/source_del', '操作-删除', '1', '1', '0', '', '', '43', '40', '146103934', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('126', 'Database/export', '操作-备份', '1', '1', '0', '', '', '5', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('127', 'Database/optimize', '操作-优化', '1', '1', '0', '', '', '5', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('128', 'Database/repair', '操作-修复', '1', '1', '0', '', '', '5', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('129', 'Database/delSqlFiles', '操作-删除', '1', '1', '0', '', '', '4', '3', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('130', 'System/bxgs_state', '操作-状态', '1', '1', '0', '', '', '67', '5', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('131', 'System/bxgs_edit', '操作-修改', '1', '1', '0', '', '', '67', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('132', 'System/bxgs_runedit', '操作-改存', '1', '1', '0', '', '', '67', '2', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('134', 'System/myinfo_runedit', '个人资料修改', '1', '1', '0', '', '', '68', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('236', 'Category/del', '操作-删除', '1', '1', '0', '', '', '9', '5', '1497424900', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('230', 'Database/import', '操作-还原', '1', '1', '0', '', '', '4', '1', '1497423595', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('145', 'Auth/adminState', '操作-状态', '1', '1', '0', '', '', '16', '5', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('149', 'Auth/groupAdd', '操作-添加', '1', '1', '0', '', '', '17', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('151', 'Auth/groupRunaccess', '操作-权存', '1', '1', '0', '', '', '17', '50', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('153', 'System/bxgs_runadd', '操作-添存', '1', '1', '0', '', '', '66', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('234', 'Category/insert', '操作-添存', '1', '1', '0', '', '', '9', '2', '1497424143', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('240', 'Module/del', '操作-删除', '1', '1', '0', '', '', '190', '4', '1497425850', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('239', 'Module/moduleState', '操作-状态', '1', '1', '0', '', '', '190', '5', '1497425764', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('238', 'page/edit', '单页编辑', '1', '1', '0', '', '', '7', '2', '1497425142', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('237', 'Category/cOrder', '操作-排序', '1', '1', '0', '', '', '9', '6', '1497424979', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('161', 'Users/usersState', '操作-状态', '1', '1', '0', '', '', '29', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('162', 'Users/delall', '操作-全部删除', '1', '1', '0', '', '', '29', '4', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('163', 'Users/edit', '操作-编辑', '1', '1', '0', '', '', '29', '2', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('164', 'Users/usersDel', '操作-删除', '1', '1', '0', '', '', '29', '3', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('247', 'Message/del', '操作-删除', '1', '1', '0', '', '', '48', '1', '1497427449', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('166', 'Users/groupOrder', '操作-排序', '1', '1', '0', '', '', '38', '50', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('167', 'Users/groupAdd', '操作-添加', '1', '1', '0', '', '', '38', '10', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('169', 'Users/groupDel', '操作-删除', '1', '1', '0', '', '', '38', '30', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('170', 'Ad/add', '操作-添加', '1', '1', '0', '', '', '45', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('171', 'Ad/edit', '操作-修改', '1', '1', '0', '', '', '45', '2', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('173', 'Ad/del', '操作-删除', '1', '1', '0', '', '', '45', '5', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('174', 'Ad/adOrder', '操作-排序', '1', '1', '0', '', '', '45', '4', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('175', 'Ad/editState', '操作-状态', '1', '1', '0', '', '', '45', '3', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('176', 'Ad/addType', '操作-添加', '1', '1', '0', '', '', '46', '1', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('252', 'Template/edit', '操作-编辑', '1', '1', '0', '', '', '197', '3', '1497428906', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('178', 'Ad/delType', '操作-删除', '1', '1', '0', '', '', '46', '4', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('179', 'Ad/typeOrder', '操作-排序', '1', '1', '0', '', '', '46', '3', '1461550835', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('180', 'System/source_edit', '操作-修改', '1', '1', '0', '', '', '43', '20', '1461832933', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('181', 'Auth/groupState', '操作-状态', '1', '1', '0', '', '', '17', '50', '1461834340', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('182', 'Users/groupEdit', '操作-修改', '1', '1', '0', '', '', '38', '15', '1461834780', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('183', 'Ad/editType', '操作-修改', '1', '1', '0', '', '', '46', '2', '1461834988', '1', '0');
INSERT INTO `clt_auth_rule` VALUES ('188', 'Plug/donation', '捐赠列表', '1', '1', '0', '', '', '187', '50', '1466563673', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('189', 'Module', '模型管理', '1', '1', '0', 'icon-ungroup', '', '0', '3', '1466825363', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('190', 'Module/index', '模型列表', '1', '1', '0', '', '', '189', '1', '1466826681', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('192', 'Module/edit', '操作-修改', '1', '1', '0', '', '', '190', '2', '1467007920', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('193', 'Module/add', '操作-添加', '1', '1', '0', '', '', '190', '1', '1467007955', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('196', 'Template', '模版管理', '1', '1', '0', 'icon-embed2', '', '0', '7', '1481857304', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('197', 'Template/index', '模版管理', '1', '1', '0', '', '', '196', '1', '1481857540', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('198', 'Template/insert', '操作-添存', '1', '1', '0', '', '', '197', '2', '1481857587', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('202', 'Template/add', '操作-添加', '1', '1', '0', '', '', '197', '1', '1481859447', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('203', 'Debris/index', '碎片管理', '1', '1', '0', '', '', '196', '2', '1484797759', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('204', 'Debris/edit', '操作-编辑', '1', '1', '0', '', '', '203', '2', '1484797849', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('205', 'Debris/add', '操作-添加', '1', '1', '0', '', '', '203', '1', '1484797878', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('206', 'Wechat', '微信管理', '1', '1', '0', 'icon-bubbles2', '', '0', '8', '1487063570', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('207', 'Wechat/config', '公众号管理', '1', '1', '0', '', '', '206', '1', '1487063705', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('208', 'Wechat/menu', '菜单管理', '1', '1', '0', '', '', '206', '2', '1487063765', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('209', 'Wechat/materialmessage', '消息素材', '1', '1', '0', '', '', '206', '3', '1487063834', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('212', 'Wechat/weixin', '操作-设置', '1', '1', '0', '', '', '207', '1', '1487064541', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('213', 'Wechat/addMenu', '操作-添加', '1', '1', '0', '', '', '208', '1', '1487149151', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('214', 'Wechat/editText', '操作-编辑', '1', '1', '0', '', '', '209', '2', '1487233984', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('215', 'Wechat/addText', '操作-添加', '1', '1', '0', '', '', '209', '1', '1487234062', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('232', 'Database/downFile', '操作-下载', '1', '1', '0', '', '', '4', '2', '1497423744', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('235', 'Category/catUpdate', '操作-该存', '1', '1', '0', '', '', '9', '4', '1497424301', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('241', 'Module/field', '模型字段', '1', '1', '0', '', '', '190', '6', '1497425972', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('242', 'Module/fieldStatus', '操作-状态', '1', '1', '0', '', '', '241', '4', '1497426044', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('243', 'Module/fieldAdd', '操作-添加', '1', '1', '0', '', '', '241', '1', '1497426089', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('244', 'Module/fieldEdit', '操作-修改', '1', '1', '0', '', '', '241', '2', '1497426134', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('245', 'Module/listOrder', '操作-排序', '1', '1', '0', '', '', '241', '3', '1497426179', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('246', 'Module/fieldDel', '操作-删除', '1', '1', '0', '', '', '241', '5', '1497426241', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('248', 'Message/delall', '操作-删除全部', '1', '1', '0', '', '', '48', '2', '1497427534', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('249', 'Link/edit', '操作-编辑', '1', '1', '0', '', '', '31', '2', '1497427694', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('250', 'Link/linkState', '操作-状态', '1', '1', '0', '', '', '31', '3', '1497427734', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('251', 'Link/del', '操作-删除', '1', '1', '0', '', '', '31', '4', '1497427780', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('253', 'Template/update', '操作-改存', '1', '1', '0', '', '', '197', '4', '1497428951', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('254', 'Template/delete', '操作-删除', '1', '1', '0', '', '', '197', '5', '1497429018', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('255', 'Template/images', '媒体文件管理', '1', '1', '0', '', '', '197', '6', '1497429157', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('256', 'Template/imgDel', '操作-文件删除', '1', '1', '0', '', '', '255', '1', '1497429217', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('257', 'Debris/del', '操作-删除', '1', '1', '0', '', '', '203', '3', '1497429416', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('258', 'Wechat/editMenu', '操作-编辑', '1', '1', '0', '', '', '208', '2', '1497429671', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('259', 'Wechat/menuOrder', '操作-排序', '1', '1', '0', '', '', '208', '3', '1497429707', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('260', 'Wechat/menuState', '操作-状态', '1', '1', '0', '', '', '208', '4', '1497429764', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('261', 'Wechat/delMenu', '操作-删除', '1', '1', '0', '', '', '208', '5', '1497429822', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('262', 'Wechat/createMenu', '操作-生成菜单', '1', '1', '0', '', '', '208', '6', '1497429886', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('263', 'Wechat/delText', '操作-删除', '1', '1', '0', '', '', '209', '3', '1497430020', '0', '0');
INSERT INTO `clt_auth_rule` VALUES ('265', 'Donation/index', '捐赠管理', '1', '1', '0', '', '', '28', '5', '1498101716', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('273', 'Wechat/replay', '回复设置', '1', '1', '0', '', '', '206', '4', '1508215988', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('267', 'Plugin/index', '插件管理', '1', '1', '1', 'icon-power-cord', '', '0', '8', '1501466560', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('269', 'Plugin/index', '第三方', '1', '1', '1', '', '', '267', '1', '1501466732', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('270', 'System/email', '邮箱配置', '1', '1', '0', '', '', '1', '2', '1502331829', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('272', 'Debris/type', '碎片分类', '1', '1', '1', '', '', '196', '3', '1504082720', '0', '1');
INSERT INTO `clt_auth_rule` VALUES ('278', 'Feast/index', '节日气氛', '1', '1', '1', '', '', '267', '50', '1532416207', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('279', 'Direct', '直销管理', '1', '1', '0', 'icon-paste', '', '0', '50', '1552116282', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('280', 'Direct/index', '直销制度', '1', '1', '1', '', '', '279', '50', '1552116458', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('281', 'Finance', '财务管理', '1', '1', '1', 'icon-clipboard', '', '0', '50', '1552312528', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('282', 'Finance/convert', '动态奖转阿美币列表', '1', '1', '1', '', '', '281', '50', '1552312629', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('283', 'Finance/lockPosition', '转账锁仓列表', '1', '1', '1', '', '', '281', '50', '1552312726', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('284', 'Finance/bonusStatistics', '奖金统计', '1', '1', '1', '', '', '281', '50', '1552312796', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('285', 'Finance/allocationRatio', '公司拨比', '1', '1', '1', '', '', '281', '50', '1552313039', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('286', 'Finance/financialFlow', '财务流水', '1', '1', '1', '', '', '281', '50', '1552313118', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('287', 'Finance/currencyRecharge', '货币充值', '1', '1', '1', '', '', '281', '50', '1552313240', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('288', 'Finance/currencyDeduction', '货币扣除', '1', '1', '1', '', '', '281', '50', '1552313311', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('289', 'Finance/applicationRecharge', '申请充值列表', '1', '1', '1', '', '', '281', '50', '1552313417', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('290', 'Finance/applicationForCash', '申请提现列表', '1', '1', '1', '', '', '281', '50', '1552313492', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('291', 'Users/userTree', '直推架构树', '1', '1', '1', '', '', '27', '50', '1552725898', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('292', 'Users/register', '新用户注册', '1', '1', '1', '', '', '27', '50', '1552742637', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('293', 'Users/userChart', '会员概况图示', '1', '1', '1', '', '', '27', '50', '1552802104', null, '1');
INSERT INTO `clt_auth_rule` VALUES ('294', 'Users/userContact', '会员接点图', '1', '1', '1', '', '', '27', '50', '1552831344', null, '1');
