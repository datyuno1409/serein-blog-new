import requests
from bs4 import BeautifulSoup
import re
import time

class HiHSKScraper:
    BASE_URL = "https://hihsk.com"

    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })

    def scrape_exam_list(self, level=4):
        url = f"{self.BASE_URL}/exam/list/{70 + level - 1}"
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        exams = []
        exam_cards = soup.find_all('div', class_='exam-card')
        
        for card in exam_cards:
            try:
                exam_link = card.find('a')['href']
                exam_id = exam_link.split('/')[-1]
                title = card.find('h3').text.strip()
                
                exams.append({
                    'exam_id': exam_id,
                    'title': title,
                    'url': self.BASE_URL + exam_link
                })
            except (AttributeError, IndexError):
                continue
        
        return exams

    def scrape_exam_detail(self, exam_url):
        response = self.session.get(exam_url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        questions = []
        question_divs = soup.find_all('div', class_='question-item')
        
        for q in question_divs:
            try:
                question_text = q.find('div', class_='question-content').text.strip()
                
                options = []
                option_divs = q.find_all('div', class_='option')
                for opt in option_divs:
                    options.append(opt.text.strip())
                
                explanation = q.find('div', class_='explanation')
                explanation_text = explanation.text.strip() if explanation else None
                
                questions.append({
                    'question': question_text,
                    'options': options,
                    'explanation': explanation_text
                })
            except AttributeError:
                continue
        
        return questions

    def scrape_conversations(self, level=1):
        url = f"{self.BASE_URL}/conversation/level/{level}"
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        conversations = []
        
        conv_cards = soup.find_all('div', class_='conversation-card')
        for card in conv_cards:
            try:
                title = card.find('h3').text.strip()
                content = card.find('div', class_='dialogue').text.strip()
                
                conversations.append({
                    'title': title,
                    'content': content,
                    'level': level,
                    'source': 'hihsk'
                })
            except AttributeError:
                continue
        
        return conversations

    def scrape_radicals(self):
        url = f"{self.BASE_URL}/radicals"
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        radicals = []
        
        radical_items = soup.find_all('div', class_='radical-item')
        for item in radical_items:
            try:
                character = item.find('span', class_='radical-char').text.strip()
                name = item.find('span', class_='radical-name').text.strip()
                meaning = item.find('span', class_='radical-meaning').text.strip()
                
                radicals.append({
                    'character': character,
                    'name': name,
                    'meaning': meaning,
                    'source': 'hihsk'
                })
            except AttributeError:
                continue
        
        return radicals
