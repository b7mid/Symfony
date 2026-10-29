# 🚀 Job Board & Candidature Platform (Symfony 7)

A robust web application built with **Symfony 7** for managing job postings and candidate applications, developed as part of the Polytechnique Sousse practical workshop.

## 🛠️ Tech Stack

- **Backend:** PHP 8.5.3, Symfony 7, Doctrine ORM
- **Database:** MariaDB
- **Frontend:** Bootstrap 5, Twig, AssetMapper
- **Version Control:** Git & GitHub (`b7mid/Symfony`)

---

## ✨ Key Features

- **Job Offer Management (CRUD):** Create, read, update, and delete job postings with company and location details.
- **Advanced Search:** Filter job offers dynamically using search queries.
- **Candidate Management:** Submit applications associated with specific job postings (`ManyToOne` relation).
- **CV / Resume Upload:** Secure PDF document upload functionality stored locally in `public/uploads/cvs/`.
- **User Authentication:** Secure access control and role-based permissions (`ROLE_USER`).
- **Global Listing Dashboard:** View all registered candidates and applications from the home route.

---

## ⚙️ Getting Started

### Prerequisites
- PHP 8.2 or higher (running on PHP 8.5.3)
- Composer
- MariaDB / MySQL

### Installation

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/b7mid/Symfony.git](https://github.com/b7mid/Symfony.git)
   cd Symfony
