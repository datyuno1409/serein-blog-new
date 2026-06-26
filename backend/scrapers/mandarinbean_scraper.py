import requests
from bs4 import BeautifulSoup
import re
import time

class MandarinBeanScraper:
    BASE_URL = "https://mandarinbean.com"

    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })

    def scrape_hsk_tests(self, level=1):
        tests = {'listening': [], 'reading': [], 'writing': []}
        
        for i in range(1, 4):
            test_code = f"H{level}0{i}0{1 if level < 3 else i}"
            listening_url = f"{self.BASE_URL}/{test_code.lower()}-listening/"
            reading_url = f"{self.BASE_URL}/{test_code.lower()}-reading/"
            
            try:
                listening_data = self._scrape_test_page(listening_url, 'listening')
                if listening_data:
                    tests['listening'].append(listening_data)
            except Exception as e:
                print(f"Error scraping {listening_url}: {e}")
            
            try:
                reading_data = self._scrape_test_page(reading_url, 'reading')
                if reading_data:
                    tests['reading'].append(reading_data)
            except Exception as e:
                print(f"Error scraping {reading_url}: {e}")
            
            time.sleep(1)
        
        return tests

    def _scrape_test_page(self, url, skill_type):
        response = self.session.get(url)
        if response.status_code != 200:
            return None
            
        soup = BeautifulSoup(response.content, 'html.parser')
        questions = []
        
        question_divs = soup.find_all('div', class_=re.compile('question|quiz-item'))
        
        for idx, q_div in enumerate(question_divs, 1):
            try:
                question_text = q_div.find('div', class_='question-text').text.strip()
                options = []
                
                option_divs = q_div.find_all('div', class_='option')
                for opt in option_divs:
                    options.append(opt.text.strip())
                
                audio = None
                if skill_type == 'listening':
                    audio_tag = q_div.find('audio')
                    if audio_tag:
                        audio = audio_tag.get('src')
                
                questions.append({
                    'question_number': idx,
                    'question_text': question_text,
                    'options': options,
                    'audio_url': audio,
                    'skill_type': skill_type
                })
                
            except AttributeError:
                continue
        
        return {'url': url, 'questions': questions}

    def scrape_vocabulary_list(self, level=1):
        url = f"{self.BASE_URL}/hsk-{level}-vocabulary-list/"
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        vocabulary = []
        
        table = soup.find('table', class_=re.compile('vocabulary|vocab-list'))
        if table:
            rows = table.find_all('tr')[1:]
            
            for row in rows:
                cells = row.find_all('td')
                if len(cells) >= 3:
                    vocabulary.append({
                        'hsk_level': level,
                        'chinese': cells[0].text.strip(),
                        'pinyin': cells[1].text.strip(),
                        'english': cells[2].text.strip(),
                        'source': 'mandarinbean'
                    })
        
        return vocabulary

    def scrape_reading_lessons(self):
        url = f"{self.BASE_URL}/all-lessons/"
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        lessons = []
        lesson_links = soup.find_all('a', href=re.compile('/lessons/'))
        
        for link in lesson_links[:20]:
            lesson_url = link['href'] if link['href'].startswith('http') else self.BASE_URL + link['href']
            try:
                lesson_data = self._scrape_lesson_detail(lesson_url)
                if lesson_data:
                    lessons.append(lesson_data)
            except Exception as e:
                print(f"Error scraping lesson {lesson_url}: {e}")
            time.sleep(0.5)
        
        return lessons

    def _scrape_lesson_detail(self, url):
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        try:
            title = soup.find('h1', class_='entry-title').text.strip()
            content_div = soup.find('div', class_='entry-content')
            
            chinese_text = content_div.find('div', class_='chinese-text').text.strip()
            translation = content_div.find('div', class_='translation').text.strip()
            
            audio = content_div.find('audio')
            audio_url = audio['src'] if audio else None
            
            return {
                'title': title,
                'content': chinese_text,
                'translation': translation,
                'audio_url': audio_url,
                'url': url,
                'source': 'mandarinbean'
            }
        except AttributeError:
            return None
