# ❄️ SNOWTRICKS

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/5955d0569a25412484cc736cdc008cd0)](https://app.codacy.com/gh/Psylvie/snowtricks/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)

A community site dedicated to snowboarding tricks, developed with Symfony. Users can register, post tricks, and interact in a discussion area.

# 🗒️ Description

The site is divided into several sections:

### 🎿 Public section (accessible to all)

- Home page
- List of figures
- Figure details (with videos/images and comments)
- Discussion area (read-only for visitors)
- Login page
- Registration page (with email confirmation)
- Forgotten password / Reset password

### 👤 Member section (logged in user)

- Adding messages in the discussion area
- Creating, modifying, and deleting figures (full CRUD)

### 🔐 Security

- Users must confirm their email address to activate their account
- Passwords are encrypted
- The password reset link is temporary and secure


## 🛠️ Installation

### ✅ Prerequisites

- **PHP**  8.2.12
- **Symfony** 7.1
- **MySQL** 5.7.34 or higher

## ⚙️ Project Configuration

1. Clone the GitHub repository:
    ```bash
    git clone https://github.com/Psylvie/snowtricks.git
    cd snowtricks
    ```

2. Install dependencies :
    ```bash
    composer install
    ```

3. Configure environment variables :

     If you don't already have the .env file at the root of your project, create it. Otherwise, open it to modify the following variables.
 ```ini
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
 ```

4. Create the database :
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

5.  Load the fixtures :
    ```bash
    php bin/console doctrine:fixtures:load
    ```
### 📩 **Configuring email sending with Mailjet**

This project uses **Mailjet** for sending emails, such as account validation and password resets. Here's how to configure Mailjet in the project.


### **Mailjet Setup Steps**

1. **Create a Mailjet account**
>Go to [Mailjet](https://www.mailjet.com/) and create an account to get your API keys (public key and private key).

2. **Adds Mailjet configuration to the `.env` file**
> Open the `.env` file at the root of your project and configure Mailjet as the email sending service. Add or modify the following line, replacing `PUBLIC_KEY` and `PRIVATE_KEY` with your API keys:

  PUBLIC_KEY : Replace with your Mailjet public key.

 PRIVATE_KEY: Replace with your Mailjet private key.

   ```ini
MAILER_DSN=mailjet+api://PUBLIC_KEY:PRIVATE_KEY@api.mailjet.com
   ```
3. **Verification and testing**
>  Once configured, you can test sending emails, such as registration confirmation or password reset, to verify that the service is working properly.

## 🚀 Startup

> To launch the project locally :
```bash
symfony server:start
  ```
> Then go to http://localhost:8000

## 🛠️ Support & contact
>For any questions or suggestions regarding this project, feel free to contact me via email at the following address: peuzin.sylvie.sp@gmail.com

>I am open to any ideas for improvements or additional features.

## 🙇 Author
<p text align= center> Sylvie PEUZIN  
<br> Développeuse d'application PHP/SYMFONY  

LinkedIn: [@sylvie Peuzin](https://www.linkedin.com/in/sylvie-peuzin/) </p>

