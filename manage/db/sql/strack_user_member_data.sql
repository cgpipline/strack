/*
 Navicat Premium Data Transfer

 Source Server         : mysql5.7
 Source Server Type    : MySQL
 Source Server Version : 50730
 Source Host           : 10.168.30.17:3306
 Source Schema         : strack_test

 Target Server Type    : MySQL
 Target Server Version : 50730
 File Encoding         : 65001

 Date: 06/09/2021 20:04:48
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Records of strack_user
-- ----------------------------
INSERT INTO `strack_user` VALUES (3, 'demo1', 'demo1@strack.com', '测试组长1', 'leader1', '18888888881', 9, '$2y$08$1EOrcrKRvif1hLsXxITChe2Xh4.P1DrysAdypMj.DX4XcFDxRZdh2', 'in_service', '', 0, 0, 0, '', 0, 0, 1630929670, 1630052398, '9174fb10-070f-11ec-9177-175f80a984fd', '', '', '');
INSERT INTO `strack_user` VALUES (4, 'demo2', 'demo2@strack.com', '测试组员1', 'member1', '18888888882', 9, '$2y$08$1EOrcrKRvif1hLsXxITChe2Xh4.P1DrysAdypMj.DX4XcFDxRZdh2', 'in_service', '', 0, 0, 0, '', 0, 0, 1630929670, 1630052425, 'a1760fb0-070f-11ec-bf8b-d5d2cb12ebf7', '', '', '');
INSERT INTO `strack_user` VALUES (5, 'demo3', 'demo3@strack.com', '测试组员2', 'member2', '18888888883', 9, '$2y$08$1EOrcrKRvif1hLsXxITChe2Xh4.P1DrysAdypMj.DX4XcFDxRZdh2', 'in_service', '', 0, 0, 0, '', 0, 0, 1630929670, 1630052435, 'a73979c0-070f-11ec-b092-bb0ae1fd051d', '', '', '');

SET FOREIGN_KEY_CHECKS = 1;
