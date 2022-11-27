<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once('install/inspections.php');
require_once('install/inspection_activity.php');
require_once('install/inspection_items.php');
require_once('install/inspection_members.php');



$CI->db->query("
INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('inspection', 'inspection-send-to-client', 'english', 'Send inspection to Customer', 'inspection # {inspection_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached inspection <strong># {inspection_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>inspection status:</strong> {inspection_status}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-already-send', 'english', 'inspection Already Sent to Customer', 'inspection # {inspection_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your inspection request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-declined-to-staff', 'english', 'inspection Declined (Sent to Staff)', 'Customer Declined inspection', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined inspection with number <strong># {inspection_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-accepted-to-staff', 'english', 'inspection Accepted (Sent to Staff)', 'Customer Accepted inspection', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted inspection with number <strong># {inspection_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-thank-you-to-customer', 'english', 'Thank You Email (Sent to Customer After Accept)', 'Thank for you accepting inspection', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank for for accepting the inspection.</span><br /> <br /><span style=\"font-size: 12pt;\">We look forward to doing business with you.</span><br /> <br /><span style=\"font-size: 12pt;\">We will contact you as soon as possible.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-expiry-reminder', 'english', 'inspection Expiration Reminder', 'inspection Expiration Reminder', '<p><span style=\"font-size: 12pt;\">Hello {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">The inspection with <strong># {inspection_number}</strong> will expire on <strong>{inspection_expirydate}</strong></span><br /><br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-send-to-client', 'english', 'Send inspection to Customer', 'inspection # {inspection_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached inspection <strong># {inspection_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>inspection status:</strong> {inspection_status}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-already-send', 'english', 'inspection Already Sent to Customer', 'inspection # {inspection_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your inspection request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-declined-to-staff', 'english', 'inspection Declined (Sent to Staff)', 'Customer Declined inspection', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined inspection with number <strong># {inspection_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-accepted-to-staff', 'english', 'inspection Accepted (Sent to Staff)', 'Customer Accepted inspection', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted inspection with number <strong># {inspection_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'staff-added-as-project-member', 'english', 'Staff Added as Project Member', 'New project assigned to you', '<p>Hi <br /><br />New inspection has been assigned to you.<br /><br />You can view the inspection on the following link <a href=\"{inspection_link}\">inspection__number</a><br /><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
('inspection', 'inspection-accepted-to-staff', 'english', 'inspection Accepted (Sent to Staff)', 'Customer Accepted inspection', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted inspection with number <strong># {inspection_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the inspection on the following link: <a href=\"{inspection_link}\">{inspection_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0);
");
/*
 *
 */

// Add options for inspections
add_option('delete_only_on_last_inspection', 1);
add_option('inspection_prefix', 'ISP-');
add_option('next_inspection_number', 1);
add_option('default_inspection_assigned', 9);
add_option('inspection_number_decrement_on_delete', 0);
add_option('inspection_number_format', 4);
add_option('inspection_year', date('Y'));
add_option('exclude_inspection_from_client_area_with_draft_status', 1);
add_option('predefined_clientnote_inspection', '- Staf diatas untuk melakukan riksa uji pada peralatan tersebut.
- Staf diatas untuk membuat dokumentasi riksa uji sesuai kebutuhan.');
add_option('predefined_terms_inspection', '- Pelaksanaan riksa uji harus mengikuti prosedur yang ditetapkan perusahaan pemilik alat.
- Dilarang membuat dokumentasi tanpa seizin perusahaan pemilik alat.
- Dokumen ini diterbitkan dari sistem CRM, tidak memerlukan tanda tangan dari PT. Cipta Mas Jaya');
add_option('inspection_due_after', 1);
add_option('allow_staff_view_inspections_assigned', 1);
add_option('show_assigned_on_inspections', 1);
add_option('require_client_logged_in_to_view_inspection', 0);

add_option('show_project_on_inspection', 1);
add_option('inspections_pipeline_limit', 1);
add_option('default_inspections_pipeline_sort', 1);
add_option('inspection_accept_identity_confirmation', 1);
add_option('inspection_qrcode_size', '160');
add_option('inspection_send_telegram_message', 0);


/*

DROP TABLE `tblinspections`;
DROP TABLE `tblinspection_activity`, `tblinspection_items`, `tblinspection_members`;
delete FROM `tbloptions` WHERE `name` LIKE '%inspection%';
DELETE FROM `tblemailtemplates` WHERE `type` LIKE 'inspection';



*/