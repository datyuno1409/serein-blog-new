import requests
from bs4 import BeautifulSoup
import re
import time

class MigiiScraper:
    BASE_URL = "https://hsk.migii.net/vi"

    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })

    def scrape_mock_tests(self, level=1):
        url = f"{self.BASE_URL}/hsk-{level}/mock-test"
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        tests = []
        
        test_cards = soup.find_all('div', class_='test-card')
        
        for card in test_cards:
            try:
                test_id = card.get('data-test-id')
                title = card.find('h3').text.strip()
                question_count = card.find('span', class_='question-count').text.strip()
                
                tests.append({
                    'test_id': test_id,
                    'title': title,
                    'question_count': question_count,
                    'hsk_level': level,
                    'source': 'migii'
                })
            except AttributeError:
                continue
        
        return tests

    def scrape_practice_questions(self, level=1, skill='listening'):
        url = f"{self.BASE_URL}/hsk-{level}/practice-{skill}"
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        questions = []
        
        question_items = soup.find_all('div', class_='practice-item')
        for item in question_items:
            try:
                question_text = item.find('div', class_='question').text.strip()
                
                options = []
                opt_divs = item.find_all('div', class_='answer-option')
                for opt in opt_divs:
                    options.append(opt.text.strip())
                
                explanation = item.find('div', class_='explanation')
                exp_text = explanation.text.strip() if explanation else None
                
                questions.append({
                    'question': question_text,
                    'options': options,
                    'correct_answer': None,
                    'explanation': exp_text,
                    'skill': skill,
                    'hsk_level': level,
                    'source': 'migii'
                })
            except AttributeError:
                continue
        
        return questions
