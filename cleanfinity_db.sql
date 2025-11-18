-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2025 at 02:43 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cleanfinity_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` enum('pay_now','pay_later') DEFAULT 'pay_later',
  `payment_status` enum('unpaid','paid','partially_paid') DEFAULT 'unpaid',
  `preferred_completion_date` date DEFAULT NULL,
  `booking_status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `employee_id`, `service_id`, `staff_id`, `booking_date`, `booking_time`, `duration_minutes`, `total_price`, `status`, `notes`, `special_instructions`, `address`, `created_at`, `updated_at`, `payment_method`, `payment_status`, `preferred_completion_date`, `booking_status`) VALUES
(1, 3, NULL, 1, NULL, '2026-02-02', '08:30:00', 120, 89.99, 'pending', NULL, 'jk rj', 'fvfcdvrh', '2025-09-30 06:00:04', '2025-09-30 06:00:04', 'pay_later', 'unpaid', NULL, 'pending'),
(2, 3, NULL, 4, NULL, '2025-10-04', '10:30:00', 300, 199.99, 'in_progress', NULL, '432', '3442', '2025-09-30 06:03:36', '2025-10-01 09:36:51', 'pay_later', 'unpaid', NULL, 'pending'),
(3, 3, NULL, 1, NULL, '2025-10-12', '13:00:00', 120, 89.99, 'pending', NULL, ' ncr', 'lt jvfc', '2025-10-01 10:22:38', '2025-10-01 10:22:38', 'pay_later', 'unpaid', NULL, 'pending'),
(4, 3, 6, 3, NULL, '2025-10-03', '12:30:00', 180, 129.99, 'completed', '', 'f', '543', '2025-10-01 10:27:53', '2025-10-03 15:49:22', 'pay_later', 'unpaid', NULL, 'pending'),
(5, 7, NULL, 3, NULL, '2025-11-23', '13:00:00', 180, 129.99, 'completed', NULL, 'jmetk', 'nlttttttw', '2025-10-01 15:19:45', '2025-10-07 02:56:30', 'pay_later', 'paid', NULL, 'pending'),
(6, 8, 4, 1, NULL, '2026-02-12', '13:30:00', 120, 89.99, 'completed', NULL, 'dc', 'dds', '2025-10-03 15:14:51', '2025-10-03 17:48:46', 'pay_later', 'unpaid', NULL, 'pending'),
(7, 8, 6, 1, NULL, '2025-11-04', '13:00:00', 120, 89.99, 'completed', NULL, 'vyk', 'trffvt', '2025-10-03 15:20:05', '2025-10-03 17:48:22', 'pay_later', 'unpaid', NULL, 'pending'),
(8, 9, 4, 3, NULL, '2025-10-08', '08:30:00', 180, 129.99, 'confirmed', NULL, '23', '123', '2025-10-07 02:59:45', '2025-10-07 03:01:13', 'pay_later', 'unpaid', NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
  `due_date` date NOT NULL,
  `issued_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `booking_id`, `invoice_number`, `amount`, `tax_amount`, `total_amount`, `status`, `due_date`, `issued_date`, `paid_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, 'INV-20251003-5073', 129.99, 0.00, 129.99, '', '2025-10-17', '2025-10-03', NULL, '', '2025-10-03 17:12:29', '2025-10-03 17:12:29'),
(2, 6, 'INV-20251003-4403', 89.99, 0.00, 89.99, '', '2025-11-02', '2025-10-03', NULL, '', '2025-10-03 17:49:04', '2025-10-03 17:49:04'),
(3, 7, 'INV-20251003-8912', 89.99, 0.00, 89.99, '', '2025-11-02', '2025-10-03', NULL, '', '2025-10-03 20:24:25', '2025-10-03 20:24:25'),
(4, 5, 'INV-20251003-7946', 129.99, 0.00, 129.99, 'paid', '2025-12-02', '2025-10-03', '2025-10-07', '', '2025-10-03 20:27:29', '2025-10-07 02:56:30'),
(5, 8, 'INV-20251007-6863', 129.99, 0.00, 129.99, '', '2025-10-14', '2025-10-07', NULL, '', '2025-10-07 03:01:39', '2025-10-07 03:01:39');

-- --------------------------------------------------------

--
-- Table structure for table `learning_modules`
--

CREATE TABLE `learning_modules` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('pdf','video','document') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learning_modules`
--

INSERT INTO `learning_modules` (`id`, `title`, `content`, `description`, `file_path`, `file_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Safety Protocols', 'This module covers the essential safety guidelines every cleaning staff must follow. It explains correct use of Personal Protective Equipment (PPE), handling of cleaning chemicals, prevention of slips/falls, and reporting of incidents. Staff should strictly follow these rules to protect themselves, customers, and the workplace.', 'Detailed safety guidelines for cleaning staff, including PPE, chemical safety, and workplace precautions.', '/uploads/modules/safety_protocols.pdf', 'pdf', 1, '2025-09-28 15:57:13', '2025-10-03 15:48:15'),
(2, 'Customer Service Excellence', 'This module focuses on building trust and professionalism when dealing with customers. It explains how to greet clients, listen actively, communicate clearly, manage expectations, and resolve complaints politely. Staff are reminded to maintain a positive and professional attitude at all times.', 'Best practices for customer interaction, communication, and professionalism.', '/uploads/modules/customer_service.pdf', 'pdf', 1, '2025-09-28 15:57:13', '2025-10-03 15:48:15'),
(3, 'Cleaning Techniques', 'This module introduces effective cleaning techniques used in different environments such as homes, offices, and commercial spaces. It covers systematic cleaning (top-to-bottom, back-to-front), proper use of tools and chemicals, sanitization methods, and quality checks to ensure professional results.', 'Practical cleaning techniques and best practices for quality service delivery.', '/uploads/modules/cleaning_techniques.pdf', 'pdf', 1, '2025-09-28 15:57:13', '2025-10-03 15:48:15'),
(4, 'Welcome to Cleanfinity', 'Welcome to the Cleanfinity team! This tutorial will help you get started with our cleaning services platform.\n\nAs a staff member, you will be assigned various cleaning jobs by the admin. Your main responsibilities include:\n\n1. Checking your dashboard regularly for new assignments\n2. Arriving on time for scheduled bookings\n3. Providing excellent service to our customers\n4. Updating booking status as you complete jobs\n5. Maintaining professional communication with customers\n\nRemember, customer satisfaction is our top priority!', NULL, '', 'pdf', 1, '2025-10-03 15:38:07', '2025-10-03 15:38:07'),
(5, 'Safety Guidelines', 'Safety is paramount in our cleaning operations. Please follow these essential safety guidelines:\n\n1. Personal Protective Equipment (PPE):\n   - Always wear gloves when handling cleaning chemicals\n   - Use safety goggles when working with spray cleaners\n   - Wear non-slip shoes to prevent accidents\n\n2. Chemical Safety:\n   - Never mix different cleaning products\n   - Read labels carefully before use\n   - Ensure proper ventilation when using strong chemicals\n   - Store chemicals in their original containers\n\n3. Equipment Safety:\n   - Inspect equipment before each use\n   - Report any damaged equipment immediately\n   - Follow manufacturer instructions\n\n4. Customer Property:\n   - Handle customer belongings with care\n   - Report any accidental damage immediately\n   - Respect customer privacy and property\n\nIf you have any safety concerns, contact your supervisor immediately.', NULL, '', 'pdf', 1, '2025-10-03 15:38:07', '2025-10-03 15:38:07'),
(6, 'Cleaning Best Practices', 'Follow these best practices to deliver exceptional cleaning services:\n\n1. Preparation:\n   - Arrive 5-10 minutes early to assess the space\n   - Bring all necessary supplies and equipment\n   - Review any special instructions from the customer\n\n2. Systematic Approach:\n   - Work from top to bottom (dust ceiling fans before floors)\n   - Clean from back to front (start at far end, work toward exit)\n   - Use the right tools for each surface\n\n3. Attention to Detail:\n   - Check corners and hard-to-reach areas\n   - Wipe down light switches and door handles\n   - Ensure mirrors and glass are streak-free\n\n4. Time Management:\n   - Follow the estimated duration for each service\n   - Prioritize high-traffic and visible areas\n   - Communicate with customers if additional time is needed\n\n5. Final Inspection:\n   - Walk through the space before leaving\n   - Ensure all areas meet our quality standards\n   - Ask customer if they are satisfied with the service', NULL, '', 'pdf', 1, '2025-10-03 15:38:07', '2025-10-03 15:38:07'),
(7, 'Customer Communication', 'Effective communication builds trust and ensures customer satisfaction:\n\n1. Before the Service:\n   - Confirm appointment 24 hours in advance\n   - Call if you will be late (even 5 minutes)\n   - Introduce yourself professionally upon arrival\n\n2. During the Service:\n   - Ask about specific areas of concern\n   - Respect customer preferences and boundaries\n   - Keep noise levels appropriate\n   - Avoid using customer phone or facilities without permission\n\n3. After the Service:\n   - Do a walkthrough with the customer if they are present\n   - Explain what was done and any recommendations\n   - Thank them for choosing Cleanfinity\n\n4. Professional Conduct:\n   - Maintain a positive and friendly attitude\n   - Dress in clean, professional attire\n   - Avoid personal phone use during service\n   - Respect customer privacy and confidentiality\n\n5. Handling Issues:\n   - Listen carefully to customer concerns\n   - Apologize for any issues\n   - Offer immediate solutions when possible\n   - Report significant issues to your supervisor', NULL, '', 'pdf', 1, '2025-10-03 15:38:07', '2025-10-03 15:38:07'),
(8, 'Using the Staff Dashboard', 'Learn how to effectively use your staff dashboard:\n\n1. Dashboard Overview:\n   - View your statistics and performance metrics\n   - See your average rating from customer reviews\n   - Check total bookings and completed jobs\n\n2. My Bookings:\n   - View all assigned bookings\n   - See customer details and contact information\n   - Check service address and special instructions\n   - Update booking status (Pending → In Progress → Completed)\n\n3. Schedule:\n   - View your upcoming assignments in calendar format\n   - Plan your route and time management\n   - Check for any scheduling conflicts\n\n4. Reviews:\n   - Read customer feedback and ratings\n   - Learn from constructive criticism\n   - Take pride in positive reviews\n\n5. Profile:\n   - Keep your contact information up to date\n   - Update your availability status\n   - Manage your account settings\n\n6. Learning Module:\n   - Access training materials anytime\n   - Mark tutorials as completed\n   - Review guidelines when needed\n\nRegularly check your dashboard for new assignments and updates!', NULL, '', 'pdf', 1, '2025-10-03 15:38:07', '2025-10-03 15:38:07'),
(9, 'Customer Service Excellence', 'Welcome to Customer Service Excellence Training!\r\n\r\nThis module will help you provide outstanding service to our clients.\r\n\r\nKey Principles:\r\n1. Always greet customers with a warm, friendly smile\r\n2. Listen actively to understand their needs and concerns\r\n3. Respond promptly to all inquiries and requests\r\n4. Be professional and courteous at all times\r\n5. Go the extra mile to exceed expectations\r\n\r\nCommunication Tips:\r\n- Use positive language and tone\r\n- Avoid jargon or technical terms customers may not understand\r\n- Confirm understanding by summarizing key points\r\n- Always thank customers for their business\r\n\r\nHandling Difficult Situations:\r\n- Stay calm and patient\r\n- Acknowledge the customer\'s feelings\r\n- Apologize sincerely when appropriate\r\n- Focus on finding solutions\r\n- Escalate to a supervisor if needed\r\n\r\nRemember: Every interaction is an opportunity to build trust and loyalty!', NULL, '', 'pdf', 1, '2025-10-03 15:43:26', '2025-10-03 15:43:26'),
(10, 'Cleaning Techniques', 'Professional Cleaning Techniques Guide\r\n\r\nThis tutorial covers essential cleaning methods and best practices.\r\n\r\nGeneral Cleaning Principles:\r\n1. Always work from top to bottom\r\n2. Clean from the farthest point toward the exit\r\n3. Use the right tools and products for each surface\r\n4. Follow manufacturer instructions for cleaning products\r\n5. Ensure proper ventilation when using chemicals\r\n\r\nKitchen Cleaning:\r\n- Countertops: Use appropriate disinfectant, wipe in circular motions\r\n- Appliances: Clean exterior surfaces, remove fingerprints\r\n- Sink: Scrub with non-abrasive cleaner, sanitize drain area\r\n- Floors: Sweep first, then mop with suitable floor cleaner\r\n\r\nBathroom Cleaning:\r\n- Toilets: Apply cleaner, let sit, scrub thoroughly, flush\r\n- Showers/Tubs: Remove soap scum, clean grout, rinse well\r\n- Mirrors: Use glass cleaner, wipe with microfiber cloth\r\n- Floors: Disinfect thoroughly, pay attention to corners\r\n\r\nLiving Areas:\r\n- Dust all surfaces including furniture, shelves, and decorations\r\n- Vacuum carpets and rugs thoroughly\r\n- Clean windows and glass surfaces\r\n- Organize items neatly\r\n\r\nSafety Reminders:\r\n- Wear appropriate protective equipment (gloves, masks)\r\n- Never mix cleaning chemicals\r\n- Read product labels carefully\r\n- Keep cleaning supplies away from children and pets', NULL, '', 'pdf', 1, '2025-10-03 15:43:26', '2025-10-03 15:43:26'),
(11, 'Time Management for Cleaning Staff', 'Effective Time Management Strategies\r\n\r\nLearn how to maximize efficiency and complete jobs on schedule.\r\n\r\nPlanning Your Day:\r\n1. Review your schedule each morning\r\n2. Gather all necessary supplies before starting\r\n3. Prioritize tasks based on client requirements\r\n4. Allow buffer time for unexpected situations\r\n5. Communicate any delays immediately\r\n\r\nEfficient Work Methods:\r\n- Carry all needed supplies with you to avoid multiple trips\r\n- Complete similar tasks together (all dusting, then all vacuuming)\r\n- Use both hands when possible\r\n- Minimize distractions and stay focused\r\n- Take short breaks to maintain energy levels\r\n\r\nTime-Saving Tips:\r\n- Prepare cleaning solutions in advance\r\n- Use microfiber cloths for faster cleaning\r\n- Keep tools organized and easily accessible\r\n- Learn shortcuts for common tasks\r\n- Practice makes perfect - speed comes with experience\r\n\r\nQuality vs. Speed:\r\n- Never sacrifice quality for speed\r\n- Double-check your work before leaving\r\n- Address any issues immediately\r\n- Build a reputation for thoroughness\r\n- Satisfied clients lead to repeat business\r\n\r\nManaging Multiple Clients:\r\n- Keep detailed notes on each client\'s preferences\r\n- Set realistic expectations for completion times\r\n- Communicate clearly about scheduling\r\n- Be punctual and reliable\r\n- Follow up after each service', NULL, '', 'pdf', 1, '2025-10-03 15:43:26', '2025-10-03 15:43:26'),
(12, 'Safety and Health Guidelines', 'Workplace Safety and Health Standards\r\n\r\nYour safety is our top priority. Follow these guidelines at all times.\r\n\r\nPersonal Protective Equipment (PPE):\r\n1. Wear gloves when handling chemicals\r\n2. Use masks in dusty environments\r\n3. Wear non-slip shoes to prevent falls\r\n4. Use knee pads for floor work\r\n5. Wear safety goggles when necessary\r\n\r\nChemical Safety:\r\n- Read all product labels and Safety Data Sheets (SDS)\r\n- Store chemicals in original containers\r\n- Never mix different cleaning products\r\n- Ensure proper ventilation\r\n- Know the location of eyewash stations and first aid kits\r\n\r\nPreventing Injuries:\r\n- Use proper lifting techniques (bend knees, not back)\r\n- Use step stools or ladders for high areas\r\n- Keep work areas clear of tripping hazards\r\n- Report damaged equipment immediately\r\n- Take breaks to prevent repetitive strain injuries\r\n\r\nElectrical Safety:\r\n- Check cords and plugs before use\r\n- Keep electrical equipment away from water\r\n- Unplug equipment when not in use\r\n- Never overload electrical outlets\r\n- Report any electrical issues immediately\r\n\r\nEmergency Procedures:\r\n- Know emergency exit locations\r\n- Understand fire extinguisher locations and use\r\n- Report all accidents and injuries immediately\r\n- Keep emergency contact numbers accessible\r\n- Follow company protocols for emergencies\r\n\r\nHealth and Hygiene:\r\n- Wash hands frequently and thoroughly\r\n- Stay home if you\'re sick\r\n- Cover coughs and sneezes\r\n- Maintain good personal hygiene\r\n- Report any health concerns to your supervisor\r\n\r\nRemember: If something doesn\'t feel safe, stop and ask for help!', NULL, '', 'pdf', 1, '2025-10-03 15:43:26', '2025-10-03 15:43:26'),
(13, 'Professional Appearance and Conduct', 'Maintaining Professional Standards\r\n\r\nRepresenting Cleanfinity with pride and professionalism.\r\n\r\nDress Code:\r\n1. Wear clean, neat company uniform\r\n2. Closed-toe, non-slip shoes required\r\n3. Keep hair tied back if long\r\n4. Minimal jewelry for safety\r\n5. Name badge must be visible\r\n\r\nPersonal Hygiene:\r\n- Maintain good personal cleanliness\r\n- Use deodorant\r\n- Keep nails clean and trimmed\r\n- Avoid strong perfumes or colognes\r\n- Present a professional appearance at all times\r\n\r\nProfessional Behavior:\r\n- Arrive on time for all appointments\r\n- Be respectful of client property\r\n- Maintain confidentiality\r\n- Avoid personal phone use during work\r\n- Stay focused on assigned tasks\r\n\r\nClient Interactions:\r\n- Greet clients politely\r\n- Listen to special requests\r\n- Ask questions if instructions are unclear\r\n- Respect client privacy\r\n- Thank clients for their business\r\n\r\nWorkplace Ethics:\r\n- Be honest and trustworthy\r\n- Report any damages or issues immediately\r\n- Never take items from client homes\r\n- Respect cultural and personal differences\r\n- Maintain professional boundaries\r\n\r\nTeam Collaboration:\r\n- Communicate effectively with colleagues\r\n- Help team members when needed\r\n- Share knowledge and tips\r\n- Participate in team meetings\r\n- Support company goals and values\r\n\r\nBuilding Your Reputation:\r\n- Consistency is key\r\n- Take pride in your work\r\n- Seek feedback and improve\r\n- Be reliable and dependable\r\n- Represent Cleanfinity positively in the community', NULL, '', 'pdf', 1, '2025-10-03 15:43:26', '2025-10-03 15:43:26');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit_card','cash','bank_transfer') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `card_last_four` varchar(4) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_response` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rosters`
--

CREATE TABLE `rosters` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `base_price` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration`, `base_price`, `duration_minutes`, `category`, `status`, `created_at`, `image_url`, `is_active`) VALUES
(1, 'Basic House Cleaning', 'Regular maintenance cleaning including dusting, vacuuming, mopping, and bathroom cleaning. Perfect for weekly or bi-weekly maintenance.', 0.00, 0, 89.99, 120, NULL, 'active', '2025-09-29 08:46:04', 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=400&h=300&fit=crop&auto=format', 1),
(2, 'Deep Cleaning', 'Comprehensive deep cleaning service including baseboards, inside appliances, detailed bathroom scrubbing, and hard-to-reach areas.', 0.00, 0, 159.99, 240, NULL, 'active', '2025-09-29 08:46:04', 'https://images.unsplash.com/photo-1527515637462-cff94eecc1ac?w=400&h=300&fit=crop&auto=format', 1),
(3, 'Office Cleaning', 'Professional office cleaning including desk sanitization, floor care, restroom maintenance, and common area cleaning.', 0.00, 0, 129.99, 180, NULL, 'active', '2025-09-29 08:46:04', 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=400&h=300&fit=crop&auto=format', 1),
(4, 'Move-in/Move-out Cleaning', 'Complete cleaning for moving situations including inside cabinets, appliances, detailed floor care, and preparing space for new occupants.', 0.00, 0, 199.99, 300, NULL, 'active', '2025-09-29 08:46:04', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=300&fit=crop&auto=format', 1);

-- --------------------------------------------------------

--
-- Table structure for table `service_ratings`
--

CREATE TABLE `service_ratings` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL COMMENT 'Allowed range: 1-5',
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_ratings`
--

INSERT INTO `service_ratings` (`id`, `booking_id`, `customer_id`, `rating`, `comment`, `created_at`) VALUES
(1, 4, 3, 4, '', '2025-10-03 16:26:46');

-- --------------------------------------------------------

--
-- Table structure for table `staff_availability`
--

CREATE TABLE `staff_availability` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_learning_progress`
--

CREATE TABLE `staff_learning_progress` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `status` enum('incomplete','completed') DEFAULT 'incomplete',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_learning_progress`
--

INSERT INTO `staff_learning_progress` (`id`, `staff_id`, `module_id`, `status`, `completed_at`, `created_at`) VALUES
(1, 6, 1, 'completed', '2025-10-03 15:38:40', '2025-10-03 15:38:40'),
(2, 6, 2, 'completed', '2025-10-03 15:45:06', '2025-10-03 15:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','staff','admin') DEFAULT 'customer',
  `status` enum('active','inactive') DEFAULT 'active',
  `availability` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `role`, `status`, `availability`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@cleanfinity.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '789', NULL, 'admin', 'active', 'available', '2025-09-28 15:50:12', '2025-09-30 16:00:14'),
(3, 'user1', 'user@gmail.com', '$2y$10$CrmYxheEQ1ULmKAIdBAPTeZz0kgyE/nmOji3.hQXeu29q4EYDilVC', 'user', '1', '87654567', 'Y890', 'customer', 'active', 'available', '2025-09-29 09:03:09', '2025-09-29 09:03:09'),
(4, 'josh123', 'josh@cleanfinity.com', '$2y$10$pkB7D3TBuyy558UBrY8t4eBWI5pkunXydffVDnFhvxoInnESRe//G', 'Mark', 'josh', '8767564', '', 'staff', 'active', 'available', '2025-09-30 19:59:49', '2025-10-03 20:09:36'),
(6, 'jay', 'jay@cleanfinity.com', '$2y$10$dx2hd8C826orW8lwXch3kOfQezprECvGL6Q7faJgK.4LpxVJUUEV6', 'employee', 'jay', '098654', NULL, 'staff', 'active', '', '2025-09-30 20:56:56', '2025-10-03 16:23:46'),
(7, 'jeff', 'jeff@gmail.com', '$2y$10$DzPYwqO3P3gdtsx5EudCSu.m61jJGHURTXmGs0EbvwLMjpWu9mCWS', 'jeff', 'benz', '2332', '43d', 'customer', 'active', 'available', '2025-10-01 15:18:44', '2025-10-01 15:18:44'),
(8, 'steveuser', 'steve@gmail.com', '$2y$10$.FZG7xGaRYPWlNeLyxuKOe4S6hdRsZ1RqE2I4bKJ7eJpzuR.QSRkC', 'steve', 'nelly', '+1456789876', '', 'customer', 'active', 'available', '2025-10-03 15:13:08', '2025-10-03 15:13:08'),
(9, 'nick21', 'nick@gmail.com', '$2y$10$/b4aG/v8A0VfEG.8o9l5U.XYoGeru6TJjXUjyIoIAotgUeqg1bBqe', 'nick', 'user', '0976667', '66', 'customer', 'active', 'available', '2025-10-07 02:58:54', '2025-10-07 02:58:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `fk_bookings_employee` (`employee_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `learning_modules`
--
ALTER TABLE `learning_modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `rosters`
--
ALTER TABLE `rosters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_ratings`
--
ALTER TABLE `service_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_booking_rating` (`booking_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff_learning_progress`
--
ALTER TABLE `staff_learning_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_staff_module` (`staff_id`,`module_id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_module_id` (`module_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `learning_modules`
--
ALTER TABLE `learning_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rosters`
--
ALTER TABLE `rosters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `service_ratings`
--
ALTER TABLE `service_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff_availability`
--
ALTER TABLE `staff_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_learning_progress`
--
ALTER TABLE `staff_learning_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_bookings_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `rosters`
--
ALTER TABLE `rosters`
  ADD CONSTRAINT `rosters_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rosters_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_ratings`
--
ALTER TABLE `service_ratings`
  ADD CONSTRAINT `service_ratings_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_ratings_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD CONSTRAINT `staff_availability_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `staff_learning_progress`
--
ALTER TABLE `staff_learning_progress`
  ADD CONSTRAINT `staff_learning_progress_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_learning_progress_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `learning_modules` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
