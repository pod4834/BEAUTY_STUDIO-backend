<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ご予約受付のお知らせ</title>
</head>
<body style="font-family: sans-serif; background-color: #f8fafc; padding: 40px 20px; color: #0f172a;">
    
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; border-top: 4px solid #0f172a;">
        <h1 style="color: #D4AF37; font-size: 24px; text-align: center; margin-bottom: 30px; letter-spacing: 2px;">CELLVIA</h1>
        
        <p style="font-size: 16px; margin-bottom: 20px;">
            <strong>{{ $reservation->name }} 様</strong>
        </p>
        
        <p style="font-size: 15px; line-height: 1.6; color: #475569; margin-bottom: 30px;">
            この度はCELLVIAへのご予約、誠にありがとうございます。<br>
            以下の内容でご予約を承りました。
        </p>

        <div style="background-color: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <p style="margin: 0 0 10px 0; font-size: 14px;"><strong>【ご予約内容】</strong></p>
            <p style="margin: 0 0 8px 0; font-size: 15px;">
                ▪ コース: {{ $reservation->menu_id == 1 ? 'Beauty Care' : 'About Business' }}
            </p>
            <p style="margin: 0 0 8px 0; font-size: 15px;">
                ▪ 日時: {{ str_replace('-', '/', $reservation->date) }} {{ $reservation->time }}
            </p>
            <p style="margin: 0 0 8px 0; font-size: 15px;">
                ▪ お電話番号: {{ $reservation->phone }}
            </p>
        </div>

        <!-- 💡 [추가됨] 예약 확인/변경/취소 버튼 영역 -->
        <div style="text-align: center; margin-bottom: 30px;">
            <p style="font-size: 14px; color: #475569; margin-bottom: 15px;">ご予約の確認・変更・キャンセルは以下のリンクよりお願いいたします。</p>
            <a href="http://localhost:5174/reservation/manage/{{ $reservation->id }}" style="display: inline-block; padding: 15px 30px; background-color: #0f172a; color: #D4AF37; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px; letter-spacing: 1px;">
                予約の確認・変更・キャンセル
            </a>
        </div>

        <!-- 💡 텍스트 수정: 당일 취소만 전화로 부탁한다는 내용으로 변경 -->
        <p style="font-size: 13px; line-height: 1.6; color: #64748b; margin-bottom: 40px;">
            ※ オンラインでの変更・キャンセルは前日まで可能です。当日の場合はお手数ですが直接お電話にてご連絡をお願いいたします。
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">
        
        <div style="text-align: center; font-size: 13px; color: #94a3b8; line-height: 1.6;">
            <strong>CELLVIA 本店</strong><br>
            〒158-0093 東京都世田谷区上野毛4-21-18<br>
            TEL: 0120-000-000<br>
            営業時間: 10:00 - 17:00 (完全予約制)
        </div>
    </div>

</body>
</html>