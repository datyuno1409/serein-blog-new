"""
Seed data for HSK vocabulary, exam questions, and reading lessons.
"""
from __future__ import annotations

from backend.database import SessionLocal
from backend.models.hsk import HSKExamQuestion, HSKReadingLesson, HSKVocabulary


HSK_VOCABULARY_SEED = [
    # HSK 1
    {"hsk_level": 1, "chinese": "你好", "pinyin": "nǐ hǎo", "vietnamese": "Xin chào", "english": "Hello",
     "example_sentences": {"zh": "你好，我是小明。", "vi": "Xin chào, tôi là Tiểu Minh."}},
    {"hsk_level": 1, "chinese": "谢谢", "pinyin": "xiè xie", "vietnamese": "Cảm ơn", "english": "Thank you",
     "example_sentences": {"zh": "谢谢你的帮助。", "vi": "Cảm ơn sự giúp đỡ của bạn."}},
    {"hsk_level": 1, "chinese": "再见", "pinyin": "zài jiàn", "vietnamese": "Tạm biệt", "english": "Goodbye",
     "example_sentences": {"zh": "明天再见！", "vi": "Ngày mai gặp lại!"}},
    {"hsk_level": 1, "chinese": "我", "pinyin": "wǒ", "vietnamese": "Tôi", "english": "I / me",
     "example_sentences": {"zh": "我是学生。", "vi": "Tôi là học sinh."}},
    {"hsk_level": 1, "chinese": "你", "pinyin": "nǐ", "vietnamese": "Bạn", "english": "You",
     "example_sentences": {"zh": "你叫什么名字？", "vi": "Bạn tên gì?"}},
    {"hsk_level": 1, "chinese": "他", "pinyin": "tā", "vietnamese": "Anh ấy", "english": "He / him",
     "example_sentences": {"zh": "他是我的朋友。", "vi": "Anh ấy là bạn của tôi."}},
    {"hsk_level": 1, "chinese": "她", "pinyin": "tā", "vietnamese": "Cô ấy", "english": "She / her",
     "example_sentences": {"zh": "她很漂亮。", "vi": "Cô ấy rất xinh đẹp."}},
    {"hsk_level": 1, "chinese": "吃", "pinyin": "chī", "vietnamese": "Ăn", "english": "To eat",
     "example_sentences": {"zh": "你想吃什么？", "vi": "Bạn muốn ăn gì?"}},
    {"hsk_level": 1, "chinese": "喝", "pinyin": "hē", "vietnamese": "Uống", "english": "To drink",
     "example_sentences": {"zh": "我喝茶。", "vi": "Tôi uống trà."}},
    {"hsk_level": 1, "chinese": "学习", "pinyin": "xué xí", "vietnamese": "Học tập", "english": "To study",
     "example_sentences": {"zh": "我在学习中文。", "vi": "Tôi đang học tiếng Trung."}},
    # HSK 2
    {"hsk_level": 2, "chinese": "考试", "pinyin": "kǎo shì", "vietnamese": "Thi cử", "english": "Exam / test",
     "example_sentences": {"zh": "明天有考试。", "vi": "Ngày mai có thi."}},
    {"hsk_level": 2, "chinese": "准备", "pinyin": "zhǔn bèi", "vietnamese": "Chuẩn bị", "english": "To prepare",
     "example_sentences": {"zh": "你准备好了吗？", "vi": "Bạn chuẩn bị xong chưa?"}},
    {"hsk_level": 2, "chinese": "已经", "pinyin": "yǐ jīng", "vietnamese": "Đã / rồi", "english": "Already",
     "example_sentences": {"zh": "我已经吃了。", "vi": "Tôi đã ăn rồi."}},
    {"hsk_level": 2, "chinese": "虽然", "pinyin": "suī rán", "vietnamese": "Mặc dù", "english": "Although",
     "example_sentences": {"zh": "虽然很难，但是很有趣。", "vi": "Mặc dù rất khó, nhưng rất thú vị."}},
    {"hsk_level": 2, "chinese": "因为", "pinyin": "yīn wèi", "vietnamese": "Bởi vì", "english": "Because",
     "example_sentences": {"zh": "因为下雨，所以我没去。", "vi": "Vì trời mưa nên tôi không đi."}},
    {"hsk_level": 2, "chinese": "手机", "pinyin": "shǒu jī", "vietnamese": "Điện thoại", "english": "Mobile phone",
     "example_sentences": {"zh": "你的手机在哪里？", "vi": "Điện thoại của bạn ở đâu?"}},
    {"hsk_level": 2, "chinese": "电脑", "pinyin": "diàn nǎo", "vietnamese": "Máy tính", "english": "Computer",
     "example_sentences": {"zh": "我用电脑工作。", "vi": "Tôi dùng máy tính để làm việc."}},
    {"hsk_level": 2, "chinese": "旁边", "pinyin": "páng biān", "vietnamese": "Bên cạnh", "english": "Beside / next to",
     "example_sentences": {"zh": "银行在学校旁边。", "vi": "Ngân hàng ở bên cạnh trường."}},
    # HSK 3
    {"hsk_level": 3, "chinese": "环境", "pinyin": "huán jìng", "vietnamese": "Môi trường", "english": "Environment",
     "example_sentences": {"zh": "我们要保护环境。", "vi": "Chúng ta cần bảo vệ môi trường."}},
    {"hsk_level": 3, "chinese": "经验", "pinyin": "jīng yàn", "vietnamese": "Kinh nghiệm", "english": "Experience",
     "example_sentences": {"zh": "他有很多工作经验。", "vi": "Anh ấy có nhiều kinh nghiệm làm việc."}},
    {"hsk_level": 3, "chinese": "决定", "pinyin": "jué dìng", "vietnamese": "Quyết định", "english": "To decide",
     "example_sentences": {"zh": "你决定了吗？", "vi": "Bạn quyết định chưa?"}},
    {"hsk_level": 3, "chinese": "练习", "pinyin": "liàn xí", "vietnamese": "Luyện tập", "english": "To practice",
     "example_sentences": {"zh": "每天练习写汉字。", "vi": "Mỗi ngày luyện tập viết chữ Hán."}},
    {"hsk_level": 3, "chinese": "提高", "pinyin": "tí gāo", "vietnamese": "Nâng cao", "english": "To improve",
     "example_sentences": {"zh": "我想提高我的中文水平。", "vi": "Tôi muốn nâng cao trình độ tiếng Trung."}},
    {"hsk_level": 3, "chinese": "关系", "pinyin": "guān xì", "vietnamese": "Quan hệ / mối quan hệ", "english": "Relationship",
     "example_sentences": {"zh": "这和你有什么关系？", "vi": "Điều này liên quan gì đến bạn?"}},
    {"hsk_level": 3, "chinese": "机会", "pinyin": "jī huì", "vietnamese": "Cơ hội", "english": "Opportunity",
     "example_sentences": {"zh": "不要错过这个机会。", "vi": "Đừng bỏ lỡ cơ hội này."}},
    {"hsk_level": 3, "chinese": "认为", "pinyin": "rèn wéi", "vietnamese": "Cho rằng / nghĩ rằng", "english": "To think / believe",
     "example_sentences": {"zh": "我认为他说得对。", "vi": "Tôi cho rằng anh ấy nói đúng."}},
]

HSK_EXAM_SEED = [
    # HSK 1 - Listening
    {"hsk_level": 1, "skill_type": "listening", "question_type": "matching",
     "question_text": "Nghe đoạn hội thoại, chọn bức ảnh phù hợp. Người phụ nữ hỏi: '你想喝什么？' Người đàn ông trả lời: '我想喝茶。'",
     "options": ["A. Hình ảnh cốc trà", "B. Hình ảnh cốc cà phê", "C. Hình ảnh chai nước", "D. Hình ảnh ly bia"],
     "correct_answer": "A", "explanation": "'茶 (chá)' nghĩa là 'trà'. Người đàn ông muốn uống trà."},
    {"hsk_level": 1, "skill_type": "listening", "question_type": "true_false",
     "question_text": "Nghe câu sau và chọn đúng hoặc sai: '今天是星期一。' (Hôm nay là thứ Hai.)",
     "options": ["Đúng", "Sai"],
     "correct_answer": "A", "explanation": "Câu nói xác nhận 今天 (hôm nay) 是 (là) 星期一 (thứ Hai)."},
    # HSK 1 - Reading
    {"hsk_level": 1, "skill_type": "reading", "question_type": "matching",
     "question_text": "Nối từ vựng với nghĩa đúng: 医院",
     "options": ["A. Trường học", "B. Bệnh viện", "C. Nhà hàng", "D. Công ty"],
     "correct_answer": "B", "explanation": "'医院 (yī yuàn)' nghĩa là bệnh viện."},
    # HSK 2 - Listening
    {"hsk_level": 2, "skill_type": "listening", "question_type": "multiple_choice",
     "question_text": "Nghe đoạn hội thoại: 男：你明天有时间吗？ 女：明天我要考试，没有时间。 Hỏi: Người phụ nữ ngày mai sẽ làm gì?",
     "options": ["A. Đi chơi", "B. Đi thi", "C. Đi làm", "D. Đi mua sắm"],
     "correct_answer": "B", "explanation": "Người phụ nữ nói '我要考试' (tôi phải thi), nên đáp án là B."},
    {"hsk_level": 2, "skill_type": "reading", "question_type": "fill_blank",
     "question_text": "Điền vào chỗ trống: 我__在学习中文，因为我想去中国工作。",
     "options": ["A. 正", "B. 已经", "C. 还", "D. 先"],
     "correct_answer": "A", "explanation": "'正在' (zhèng zài) biểu thị hành động đang diễn ra."},
    # HSK 3 - Reading
    {"hsk_level": 3, "skill_type": "reading", "question_type": "comprehension",
     "question_text": "阅读理解: 小明每天早上六点起床，先跑步三十分钟，然后吃早饭。他觉得运动对身体很好。 Hỏi: 小明早上起床后先做什么？",
     "options": ["A. 吃早饭", "B. 跑步", "C. 去上班", "D. 看书"],
     "correct_answer": "B", "explanation": "文中说'先跑步三十分钟'，所以小明起床后先跑步。"},
    {"hsk_level": 3, "skill_type": "listening", "question_type": "multiple_choice",
     "question_text": "Nghe đoạn hội thoại: 女：你觉得学习中文难不难？ 男：虽然语法不太难，但是汉字很难写。 Hỏi: Theo người đàn ông, cái gì khó?",
     "options": ["A. Ngữ pháp", "B. Phát âm", "C. Viết chữ Hán", "D. Nghe"],
     "correct_answer": "C", "explanation": "Người đàn ông nói '汉字很难写' (chữ Hán rất khó viết)."},
    {"hsk_level": 3, "skill_type": "reading", "question_type": "matching",
     "question_text": "Chọn nghĩa đúng của từ '经验':",
     "options": ["A. Kinh tế", "B. Kinh nghiệm", "C. Kinh doanh", "D. Kinh phí"],
     "correct_answer": "B", "explanation": "'经验 (jīng yàn)' nghĩa là kinh nghiệm."},
]

HSK_READING_SEED = [
    {"hsk_level": 1, "title": "我的家", "source": "seed",
     "content": "我家有五口人：爸爸、妈妈、哥哥、妹妹和我。爸爸是医生，妈妈是老师。哥哥在大学学习，妹妹还在上小学。我们住在北京。我很爱我的家。",
     "translation": "Gia đình tôi có năm người: bố, mẹ, anh trai, em gái và tôi. Bố là bác sĩ, mẹ là giáo viên. Anh trai đang học đại học, em gái vẫn đang học tiểu học. Chúng tôi sống ở Bắc Kinh. Tôi rất yêu gia đình mình.",
     "vocabulary_list": {"家": "jiā - gia đình / nhà", "爸爸": "bà ba - bố", "妈妈": "mā ma - mẹ", "医生": "yī shēng - bác sĩ", "老师": "lǎo shī - giáo viên"}},
    {"hsk_level": 1, "title": "去商店买东西", "source": "seed",
     "content": "今天是星期六，我和朋友一起去商店。我买了一本书和两个苹果。书很好看，苹果很好吃。我花了三十块钱。",
     "translation": "Hôm nay là thứ Bảy, tôi cùng bạn đi cửa hàng. Tôi mua một quyển sách và hai quả táo. Sách rất hay, táo rất ngon. Tôi tiêu hết 30 tệ.",
     "vocabulary_list": {"商店": "shāng diàn - cửa hàng", "书": "shū - sách", "苹果": "píng guǒ - quả táo", "块": "kuài - tệ (đơn vị tiền)"}},
    {"hsk_level": 2, "title": "坐公共汽车", "source": "seed",
     "content": "早上我坐公共汽车去上班。公共汽车站离我家很近，走路五分钟就到了。虽然坐地铁更快，但是公共汽车更便宜。每天早上车上的人很多，有时候没有座位。",
     "translation": "Sáng tôi đi xe buýt đi làm. Trạm xe buýt rất gần nhà tôi, đi bộ 5 phút là đến. Mặc dù đi tàu điện ngầm nhanh hơn, nhưng xe buýt rẻ hơn. Mỗi sáng trên xe rất đông người, đôi khi không có chỗ ngồi.",
     "vocabulary_list": {"公共汽车": "gōng gòng qì chē - xe buýt", "上班": "shàng bān - đi làm", "地铁": "dì tiě - tàu điện ngầm", "便宜": "pián yi - rẻ"}},
    {"hsk_level": 3, "title": "学习中文的方法", "source": "seed",
     "content": "学习中文有很多方法。首先，每天要练习听力，可以听中文歌或者看中文电影。其次，要多读中文文章，这样可以提高阅读能力。最后，不要怕说错，多和中国朋友交流。我认为只要坚持，一定能学好中文。",
     "translation": "Có nhiều phương pháp học tiếng Trung. Đầu tiên, mỗi ngày cần luyện nghe, có thể nghe nhạc Trung hoặc xem phim Trung Quốc. Tiếp theo, cần đọc nhiều bài viết tiếng Trung, như vậy có thể nâng cao khả năng đọc hiểu. Cuối cùng, đừng sợ nói sai, hãy giao lưu nhiều với bạn bè Trung Quốc. Tôi cho rằng chỉ cần kiên trì, nhất định sẽ học giỏi tiếng Trung.",
     "vocabulary_list": {"方法": "fāng fǎ - phương pháp", "练习": "liàn xí - luyện tập", "提高": "tí gāo - nâng cao", "坚持": "jiān chí - kiên trì"}},
    {"hsk_level": 3, "title": "我的梦想", "source": "seed",
     "content": "我的梦想是成为一名程序员。我现在每天学习编程，从Python开始。虽然有时候觉得很难，但是当我成功写出一个程序的时候，我感到非常高兴。我相信努力学习一定会实现梦想。",
     "translation": "Ước mơ của tôi là trở thành một lập trình viên. Hiện tại mỗi ngày tôi học lập trình, bắt đầu từ Python. Mặc dù đôi khi cảm thấy rất khó, nhưng khi tôi viết thành công một chương trình, tôi cảm thấy vô cùng vui. Tôi tin rằng nỗ lực học tập nhất định sẽ thực hiện được ước mơ.",
     "vocabulary_list": {"梦想": "mèng xiǎng - ước mơ", "程序员": "chéng xù yuán - lập trình viên", "编程": "biān chéng - lập trình", "努力": "nǔ lì - nỗ lực"}},
]


def seed_hsk_data() -> None:
    """Seed HSK vocabulary, exam questions, and reading lessons if empty."""
    db = SessionLocal()
    try:
        if db.query(HSKVocabulary).count() > 0:
            return

        for item in HSK_VOCABULARY_SEED:
            db.add(HSKVocabulary(source="seed", **item))

        for item in HSK_EXAM_SEED:
            db.add(HSKExamQuestion(source="seed", **item))

        for item in HSK_READING_SEED:
            db.add(HSKReadingLesson(**item))

        db.commit()
        print(f"[seed-hsk] Seeded {len(HSK_VOCABULARY_SEED)} vocabulary, {len(HSK_EXAM_SEED)} exams, {len(HSK_READING_SEED)} reading lessons")
    except Exception as exc:
        db.rollback()
        print(f"[seed-hsk] Error: {exc}")
    finally:
        db.close()
