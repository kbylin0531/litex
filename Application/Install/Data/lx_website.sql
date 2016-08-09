/*
Navicat MySQL Data Transfer

Source Server         : 182.61.39.187
Source Server Version : 50631
Source Host           : 182.61.39.187:3306
Source Database       : litex

Target Server Type    : MYSQL
Target Server Version : 50631
File Encoding         : 65001

Date: 2016-07-26 22:18:12
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for lx_website
-- ----------------------------
DROP TABLE IF EXISTS `lx_website`;
CREATE TABLE `lx_website` (
  `name` varchar(64) NOT NULL,
  `value` varchar(255) NOT NULL COMMENT 'logo_path',
  `title` varchar(255) NOT NULL DEFAULT 'untitled',
  `description` text,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of lx_website
-- ----------------------------
INSERT INTO `lx_website` VALUES ('logo', '/images/logo.png', 'logo', null);
INSERT INTO `lx_website` VALUES ('logo_icon', '/images/logo_icon.png', 'logo_icon', null);
INSERT INTO `lx_website` VALUES ('logo_url', '#', 'logo 点击跳转地址', null);
INSERT INTO `lx_website` VALUES ('search_url', '#', '查询地址', null);
INSERT INTO `lx_website` VALUES ('title', 'LiteX  IS', '网站标题', null);
