/*
 Navicat Premium Data Transfer

 Source Server         : mysql5.7
 Source Server Type    : MySQL
 Source Server Version : 50730
 Source Host           : 10.168.30.17:3306
 Source Schema         : strack_test2

 Target Server Type    : MySQL
 Target Server Version : 50730
 File Encoding         : 65001

 Date: 03/09/2021 14:22:16
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for strack_role_user
-- ----------------------------
DROP TABLE IF EXISTS `strack_role_user`;
CREATE TABLE `strack_role_user`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色权限ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `role_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '全局唯一标识符',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_auth_role_id`(`role_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of strack_role_user
-- ----------------------------
INSERT INTO `strack_role_user` VALUES (1, 3, 4, '289a7d10-0c7f-11ec-acf1-c314f56202ea');
INSERT INTO `strack_role_user` VALUES (2, 4, 5, '2c2bade0-0c7f-11ec-b9f6-156246aa05a4');
INSERT INTO `strack_role_user` VALUES (3, 5, 5, '2e382930-0c7f-11ec-8622-d9d5dbc49766');

SET FOREIGN_KEY_CHECKS = 1;
