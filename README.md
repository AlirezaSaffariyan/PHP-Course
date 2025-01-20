# PHP Course

## Assignment 1: Simple calculator with PHP

## Assignment 2: A Python Web Scraper related to the main project

- Python:
  - BeautifulSoup4
  - Requests

Run `pip install -r requirements.txt` to install dependencies.

Simply run `python main.py` to run the script.

## Main Project: Music Player Management Dashboard

### Technologies:

- PHP
- MySQL
- Docker
- HTML, CSS, and Javascript
- Python

### Sources:

- Spotify
- MusicBrainz
- Google

### Requirements:

- Docker
- Free Internet :)

### How to build and run?

1. Connect to the free world of the Internet.
2. Create a database and add your database user info to `project/php/env.php`.
3. Create an spotify developers account and add your client id and client secret to `project/php/env.php` after the databse info.
3. To Build and run the project for the first time, just open a terminal or commandline, head to the `project/` directory and simply run:
```bash
docker-compose up --build
```
4. Open a link to `localhost:8080` in your browser to see the application.
5. Next time, you don't need to build the project. Just to run the project, run:
```bash
docker-compose up
```
