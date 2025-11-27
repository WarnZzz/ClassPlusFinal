ClassPlus – Class Management & Smart Attendance System
------------------------------------------------------

Project Type: Web-Based Application
Technologies Used: PHP, MySQL, HTML, CSS, Bootstrap, jQuery, AJAX
Additional Libraries: PHPMailer, chillerlan QRCode, html5-qrcode
Target Users: Admin, Class Teacher, Students


1. Introduction
----------------
ClassPlus is a web-based class management and attendance automation system designed to modernize academic administration. 
The system provides tools for managing classes, teachers, students, and attendance records. Its QR-based attendance 
feature makes the attendance process accurate, reliable, and efficient. 

Other modules such as LMS functions (notes & announcements) and a class chatroom help simplify communication 
and resource sharing within the class.


2. System Objectives
---------------------
1. Automate student attendance using QR and manual code-based mechanisms  
2. Provide role-based, secure login for Admins, Teachers, and Students  
3. Allow teachers to finalize attendance digitally  
4. Enable students to track their own attendance  
5. Provide an admin dashboard for class, teacher, and student management  
6. Offer simple LMS features such as announcements and notes  
7. Support class-based chat for real-time communication  


3. Features
------------
A. Role-Based Login System  
- Admin, Teacher, and Student login  
- Email/Symbol No authentication  
- OTP login using PHPMailer  
- Session-based redirection and security  

B. Admin Panel  
- Manage classes, programs, sections, and batches  
- Add/manage teachers  
- Add/manage students  
- Assign courses to teachers  
- View complete attendance logs  
- Export attendance reports  

C. Teacher Panel  
- Start QR-based attendance session  
- Generates unique QR + manual session code  
- Students scan or enter the code  
- Real-time present list using AJAX  
- Finalize attendance (moves from temp to permanent table)  
- Manual check/uncheck option  
- Download attendance in CSV/PDF format  
- Post announcements & notes  

D. Student Panel  
- Scan QR / enter session code to mark attendance  
- View daily, monthly, and overall attendance  
- View class details and announcements  
- Access uploaded notes  

E. Class Chat System  
- One chatroom per class  
- Teachers and students can send text & file messages  
- Real-time updates with AJAX  
- Secure and role-restricted  

F. Basic LMS Integration  
- Teachers upload study materials  
- Students can download notes anytime  
- Announcement broadcasting  

G. Automated Email Notifications  
- Alerts for low attendance  
- OTP expiry control  


4. Attendance Workflow
-----------------------
Step 1: Teacher starts a session  
Step 2: QR code + session code displayed  
Step 3: Students scan the QR or manually enter the code  
Step 4: Attendance is recorded in `tblattendance_temp`  
Step 5: Teacher sees real-time updates through AJAX  
Step 6: Teacher finalizes attendance (stored in `tblattendance`)  


5. System Architecture
-----------------------
Backend: PHP  
Frontend: HTML, CSS, Bootstrap, jQuery  
Database: MySQL  
QR Code Generation: chillerlan/qrcode  
QR Scanning: html5-qrcode  
Email System: PHPMailer  

Architecture Layers:  
- Presentation Layer (UI)  
- Application Layer (PHP scripts)  
- Database Layer (MySQL)  


6. Database Overview
---------------------
Main Tables:  
- tbladmin  
- tblclassteacher  
- tblstudents  
- tblclass  
- tblclassarms  
- tblattendance  
- tblattendance_temp  
- tblattendance_sessions  
- chat_message tables  
- notes/announcement tables  

Key Relationships:  
- ClassId → links students and classarms to class  
- CourseId → links attendance entries to classarms  
- Temp attendance table ensures accuracy before saving  


7. Installation Guide
----------------------
1. Install XAMPP/WAMP  
2. Copy project folder to htdocs  
3. Create MySQL database and import SQL file  
4. Configure `config.php` with:  
   - Database host  
   - DB username/password  
   - SMTP credentials  
5. Install PHPMailer and QRCode libraries  
6. Run the project in browser  
7. Add admin/teacher/student accounts  


8. System Requirements
-----------------------
Software Requirements:  
- PHP 7.4+  
- MySQL 5.7+  
- Apache server  
- Composer (optional)  

Browser Requirements:  
- Chrome/Firefox/Edge  
- Camera permission for QR scan  


9. Future Expansion
--------------------
- WebAuthn biometric login (Fingerprint / Face ID)  
- Mobile App (Android/iOS)  
- RFID/NFC attendance  
- AI-powered attendance prediction  
- Face Recognition Attendance  
- Integration with Google Classroom / full LMS  
- Advanced analytics dashboard  


10. Conclusion
----------------
ClassPlus modernizes the classroom with automated attendance, class communication tools, 
and basic LMS features. Its design focuses on reducing manual effort and improving 
accuracy and transparency in attendance tracking. Future expansions such as biometric 
authentication, mobile apps, and AI analytics can make ClassPlus even more powerful.

