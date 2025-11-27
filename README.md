ClassPlus

A modern class management and smart attendance system built with PHP, MySQL, Bootstrap, jQuery, and QR-based attendance.
It supports Admin, Teacher, and Student roles with secure login, LMS functions, and real-time attendance tracking.

Features
1. Role-Based Login

Admin, Class Teacher, and Student roles

Email/Symbol No login

OTP-based verification using PHPMailer

Secure session-based redirection

2. Class, Teacher & Student Management (Admin Panel)

Add and manage classes, sections, and programs

Add/manage class teachers

Add/manage students

Assign courses to teachers

View attendance and student activity reports

3. Student Attendance (Teacher Panel)
QR-Code Based Attendance

Teacher starts an attendance session

System generates a unique QR code and manual code

Students scan the QR or enter the code

Attendance is saved in tblattendance_temp

Teacher sees a real-time checklist of present students

After finalizing, data moves to tblattendance

Manual Attendance

Teachers can manually check/uncheck students

Daily and overall attendance reports

Attendance downloadable as CSV/PDF

Alerts

Automated email alerts for students with low attendance

4. Student Panel

View daily, monthly, and overall attendance

See personal class details and profile

Receive attendance alerts and announcements

5. Real-Time Class Chat System

One chatroom per class

Students and teachers communicate in real time

Supports text messages and file attachments

Uses AJAX for instant updates

6. Technology Stack
Frontend

Bootstrap

jQuery + AJAX

HTML5 QR scanner (html5-qrcode)

Backend

PHP

MySQL

chillerlan PHP QRCode

PHPMailer

7. Database Structure

Key Tables:

tbladmin

tblclassteacher

tblstudents

tblclass

tblclassarms

tblattendance

tblattendance_temp

tblattendance_sessions

Relationships:

ClassId connects class → students & classarms

CourseId connects classarms → attendance

Attendance stored temporarily before finalization

8. Attendance Workflow
Teacher Side

Start attendance session

Display QR + manual code

Real-time student presence update via AJAX

Finalize attendance

Student Side

Scan QR or manually enter code

Attendance added to temporary table

Mark present only during active session

9. LMS Features (Basic)

Upload/share class notes

Announcements for students

Assignment uploads (optional)

10. Installation

Clone or download the project

Import the SQL file into MySQL

Configure database credentials in config.php

Set SMTP settings for OTP and email alerts

Ensure PHP extensions: openssl, curl, mbstring, json

Install required PHP libraries (QR code + PHPMailer)

11. Future Expansion

You can extend ClassPlus with advanced features such as:

WebAuthn / Biometric Login (Fingerprint, Face ID)

RFID or NFC-based attendance

Mobile app for students/teachers

Predictive analytics using Machine Learning

Face recognition attendance system

Integration with Google Classroom or other LMS

Real-time analytics dashboard

12. License

This project is for academic and personal use.
You may modify or expand the system based on your requirements.
