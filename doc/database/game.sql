-- MySQL Script generated by MySQL Workbench
-- Sun 12 Apr 2015 09:52:12 PM EDT
-- Model: New Model    Version: 1.0
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema game
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `game` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `game` ;

-- -----------------------------------------------------
-- Table `game`.`game`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `game`.`game` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL,
  `description` TINYTEXT NULL,
  `url` VARCHAR(255) NULL,
  `genre` ENUM('board','card','other') NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;