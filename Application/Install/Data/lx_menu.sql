/*
Navicat MySQL Data Transfer

Source Server         : 182.61.39.187
Source Server Version : 50631
Source Host           : 182.61.39.187:3306
Source Database       : litex

Target Server Type    : MYSQL
Target Server Version : 50631
File Encoding         : 65001

Date: 2016-07-26 22:18:05
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for lx_menu
-- ----------------------------
DROP TABLE IF EXISTS `lx_menu`;
CREATE TABLE `lx_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT 'untitled' COMMENT '菜单项名称',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT 'fa图标',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单项URL，相对于脚本的路径或者http开头的路径',
  `order` int(10) unsigned NOT NULL DEFAULT '0',
  `parent` int(255) unsigned NOT NULL DEFAULT '0' COMMENT '为0时表示顶级菜单，否则表示特定的菜单项的子菜单项',
  `type` tinyint(255) unsigned NOT NULL DEFAULT '1' COMMENT '菜单项类型，1表示side菜单，2表示用户菜单',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of lx_menu
-- ----------------------------
INSERT INTO `lx_menu` VALUES ('1', 'Dash', 'circle', '/Admin/Index/index', '2', '0', '1');
INSERT INTO `lx_menu` VALUES ('2', 'Dash2', '', '/Admin/Index/index', '1', '0', '1');
INSERT INTO `lx_menu` VALUES ('3', 'Dash3', '', '/Admin/Index/index', '0', '1', '1');
INSERT INTO `lx_menu` VALUES ('4', 'Dash4', '', '/Admin/Index/index', '0', '1', '1');
INSERT INTO `lx_menu` VALUES ('5', 'Dash5', '', '/Admin/Index/index', '0', '1', '1');
INSERT INTO `lx_menu` VALUES ('6', 'Dash6', '', '/Admin/Index/index', '3', '0', '1');
INSERT INTO `lx_menu` VALUES ('7', 'Sign out', 'sign-out', '/Admin/Publics/logout', '0', '0', '2');
