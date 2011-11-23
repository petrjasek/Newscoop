ALTER TABLE `ArticleAuthors` ADD `order` int(2) unsigned;
ALTER TABLE `audit_event` MODIFY `resource_id` varchar(255) DEFAULT NULL;