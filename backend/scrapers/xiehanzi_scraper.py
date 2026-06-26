import requests
from bs4 import BeautifulSoup
import json
import re
import time

class XieHanziScraper:
    BASE_URL = "https://xiehanzi.com"

    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })

    def scrape_hsk_vocabulary(self, level=1):
        url = f"{self.BASE_URL}/thu-vien-hsk/hsk-{level}/"
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        vocabulary = []
        topic_links = soup.find_all('a', href=re.compile(f'/thu-vien-hsk/hsk-{level}/[^/]+/$'))
        
        for topic in topic_links[:3]:
            topic_url = self.BASE_URL + topic['href']
            vocab_list = self._scrape_topic_vocabulary(topic_url, level)
            vocabulary.extend(vocab_list)
            time.sleep(0.5)
            
        return vocabulary

    def _scrape_topic_vocabulary(self, url, level):
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        words = []
        word_cards = soup.find_all('div', class_=re.compile('word-card|vocab-item'))
        
        for card in word_cards:
            try:
                chinese = card.find('span', class_='chinese').text.strip()
                pinyin = card.find('span', class_='pinyin').text.strip()
                meaning = card.find('span', class_='meaning').text.strip()
                
                words.append({
                    'hsk_level': level,
                    'chinese': chinese,
                    'pinyin': pinyin,
                    'vietnamese': meaning,
                    'source': 'xiehanzi'
                })
            except AttributeError:
                continue
                
        return words

    def scrape_character_strokes(self, character):
        url = f"{self.BASE_URL}/han-tu/{character}/"
        response = self.session.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        stroke_data = {
            'character': character,
            'stroke_count': 0,
            'stroke_order_svg': None,
            'radical': None
        }
        
        try:
            stroke_info = soup.find('div', class_='stroke-info')
            if stroke_info:
                stroke_data['stroke_count'] = int(stroke_info.find('span', class_='count').text)
                
            svg = soup.find('svg', class_='stroke-order')
            if svg:
                stroke_data['stroke_order_svg'] = str(svg)
                
        except (AttributeError, ValueError):
            pass
            
        return stroke_data
