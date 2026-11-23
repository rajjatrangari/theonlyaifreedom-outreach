<?php
// Outreach AI Freedom - World-Class Direct Response Copywriting & Multi-Language Sequencer
// Supports English, Hinglish, Hindi, and Marathi with Real Decision-Maker Targeting & In-Chat Response Playbooks
declare(strict_types=1);

class AIGenerator {

    /**
     * Generates a complete, tailored outreach sequence across channels & regional languages.
     * Guaranteed to speak directly to the real Decision Maker (Founder / MD / CEO) — NEVER generic "Hey team".
     */
    public static function generateSequence(array $user, array $lead, string $tone = 'direct_response', string $lang = 'en'): array {
        $businessName = trim($lead['business_name'] ?? 'Your Brand');
        $niche = !empty($lead['category']) ? $lead['category'] : 'luxury commercial';
        $city = !empty($lead['city']) ? $lead['city'] : 'Mumbai';
        $reviews = (int)($lead['reviews_count'] ?? 0);
        $rating = (float)($lead['rating'] ?? 4.8);

        // Decision Maker Resolution
        $dmFullName = trim($lead['decision_maker_name'] ?? '');
        $dmTitle = trim($lead['decision_maker_title'] ?? 'Managing Director');
        
        if (empty($dmFullName) || strtolower($dmFullName) === 'team' || strtolower($dmFullName) === 'founder') {
            $dmFirstName = 'Founder';
            $dmSalutationEn = "Hi {$dmTitle}";
            $dmSalutationHinglish = "Hello {$dmTitle} ji";
            $dmSalutationHi = "नमस्ते {$dmTitle} जी";
            $dmSalutationMr = "नमस्कार {$dmTitle} जी";
        } else {
            $parts = explode(' ', $dmFullName);
            $dmFirstName = $parts[0];
            $dmSalutationEn = "Hi {$dmFirstName}";
            $dmSalutationHinglish = "Hi {$dmFirstName} ji";
            $dmSalutationHi = "नमस्ते {$dmFirstName} जी";
            $dmSalutationMr = "नमस्कार {$dmFirstName} जी";
        }

        // Sender Profile
        $senderName = !empty($user['sender_name']) ? $user['sender_name'] : (!empty($user['name']) ? $user['name'] : 'Rajjat Rangari');
        $senderTitle = !empty($user['sender_title']) ? $user['sender_title'] : 'Founder';
        $myBusiness = !empty($user['business_name']) ? $user['business_name'] : 'The Only AI Freedom';
        $myOffering = !empty($user['business_offering']) ? $user['business_offering'] : 'custom AI client acquisition workflows';
        $myUsp = !empty($user['usp_hook']) ? $user['usp_hook'] : 'We deploy a dedicated lead conversion system live in 7 days.';
        $senderPhone = $user['sender_whatsapp'] ?: ($user['sender_phone'] ?: '+917620654814');
        $senderEmail = $user['sender_email'] ?: ($user['email'] ?: 'outreach@theonlyaifreedom.in');
        $pricing = !empty($user['business_pricing']) ? $user['business_pricing'] : 'flexible ROI-linked growth packages';

        // All languages bundle
        $sequences = [
            // -------------------------------------------------------------
            // 1. ENGLISH (Direct Response, Peer-to-Peer Founder Copywriting)
            // -------------------------------------------------------------
            'en' => [
                'language_label' => 'English',
                'whatsapp' => [
                    'initial' => "{$dmSalutationEn},\n\n"
                        . "Saw your work scaling {$businessName} in {$city}.\n\n"
                        . "Quick question: Are you looking to add more high-ticket clients this quarter, or is your team already at full capacity?\n\n"
                        . "We recently built a direct acquisition workflow for a peer {$niche} brand that added 18 qualified buyers last month with zero ad waste.\n\n"
                        . "Worth a 60-second video walkthrough, or completely off base?",
                    
                    'followup_1' => "{$dmSalutationEn},\n\n"
                        . "Following up on my note regarding client acquisition for {$businessName}.\n\n"
                        . "We put together a private 90-second teardown showing how similar brands in {$city} are converting high-intent buyers directly on WhatsApp.\n\n"
                        . "Mind if I share the link here?",
                    
                    'followup_2' => "Quick bump {$dmFirstName} — wanted to ensure this didn't slip through your messages!\n\n"
                        . "Are you still exploring ways to automate growth for {$businessName}, or is that strictly on pause right now?",
                    
                    'followup_3' => "{$dmFirstName}, I assume you're completely focused on other priorities right now (completely understand).\n\n"
                        . "I won't follow up again so I don't clutter your inbox. If you ever want to see how our {$myOffering} can scale your high-ticket pipeline, feel free to reach out anytime.\n\n"
                        . "Wishing {$businessName} continued growth!",
                ],
                'linkedin' => [
                    'initial' => "{$dmSalutationEn},\n\n"
                        . "Noticed your leadership expanding {$businessName} across {$city}. Outstanding track record.\n\n"
                        . "I'm {$senderName}, {$senderTitle} at {$myBusiness}. We help premium {$niche} leaders automate direct high-ticket client acquisition.\n\n"
                        . "Would you be against taking a look at a 2-minute video teardown of how we set this up for peer brands?",
                    
                    'followup_1' => "{$dmSalutationEn}, circling back to see if testing a streamlined client capture workflow is on your radar this quarter for {$businessName}?",
                ],
                'email' => [
                    'subject' => "quick question re: {$businessName}",
                    'body' => "{$dmSalutationEn},\n\n"
                        . "Saw {$businessName}'s recent footprint in {$city}.\n\n"
                        . "Quick question: Are you currently open to adding 15-20 qualified high-ticket consultations every month on autopilot, or is client acquisition already completely handled for you?\n\n"
                        . "We deployed a dedicated conversion engine for a similar {$niche} firm that generated 3.4x more verified inquiries in 30 days.\n\n"
                        . "Would you be opposed to seeing a 2-minute walkthrough of the framework, or are you too busy right now?\n\n"
                        . "Best,\n"
                        . "{$senderName}\n"
                        . "{$senderTitle} | {$myBusiness}\n"
                        . "WhatsApp: {$senderPhone}",
                    
                    'followup_1_subject' => "re: quick question re: {$businessName}",
                    'followup_1_body' => "{$dmSalutationEn},\n\n"
                        . "Wanted to check if you had a brief moment to review my previous note?\n\n"
                        . "Happy to send over the 2-minute case study whenever convenient, or let me know if this isn't a focus for {$businessName} right now.\n\n"
                        . "Best,\n{$senderName}",
                ],
                'instagram' => [
                    'initial' => "{$dmSalutationEn}! Love what you're building with {$businessName} in {$city} ✨\n\n"
                        . "Quick question — are you looking to onboard more high-ticket clients this quarter?\n\n"
                        . "We help top {$niche} leaders capture direct buyers with {$myOffering}.\n\n"
                        . "Drop a quick 'YES' and I'll send over a 60-second breakdown video!",
                ],
                'playbook' => [
                    'reply_interested' => "Great {$dmFirstName}! Here is the private 2-minute breakdown: [Link]. It shows the exact workflow we deployed for peer brands. Do you have 10 minutes this Thursday at 4 PM or Friday at 11 AM for a quick intro call?",
                    'reply_pricing' => "Our custom client engines typically range from {$pricing}, fully tailored to your sales volume. We guarantee a live conversion pilot in 7 days or full money back. Would you be open to a 10-minute discovery call to see if the ROI makes sense for {$businessName}?",
                    'reply_have_agency' => "Completely respect that {$dmFirstName}. We don't replace your in-house team or current agency — our proprietary system acts as their 24/7 lead capture co-pilot, handing them warm pre-qualified buyers. Would you be open to seeing how we integrate alongside your existing setup?",
                    'reply_not_interested' => "Understood completely {$dmFirstName}. Appreciate the direct reply. If your team ever wants to scale high-ticket inquiries on autopilot in the future, my door is always open. Wishing you and {$businessName} massive success!",
                    'reply_who_is_this' => "I'm {$senderName}, founder of {$myBusiness}. I personally research market leaders in {$city} and reached out directly to you as {$dmTitle} of {$businessName} to share an acquisition idea that worked exceptionally well for fellow {$niche} brands. Hope it wasn't an intrusion!",
                    'reply_book_call' => "Perfect! You can grab a convenient 10-minute slot on my direct calendar here: [Your Calendar Link]. Looking forward to speaking, {$dmFirstName}!",
                ]
            ],

            // -------------------------------------------------------------
            // 2. HINGLISH (Natural Conversational Hindi-English Blend, 4x Reply Rate)
            // -------------------------------------------------------------
            'hinglish' => [
                'language_label' => 'Hinglish (Conversational)',
                'whatsapp' => [
                    'initial' => "{$dmSalutationHinglish},\n\n"
                        . "{$city} me {$businessName} ka growth aur brand presence dekh kar kaafi impress hua!\n\n"
                        . "Ek quick question tha: Kya aap abhi {$city} me new high-ticket clients expand karne ke liye open hain, ya team already fully booked hai?\n\n"
                        . "Humne recently ek similar {$niche} brand ke liye direct client engine setup kiya tha jisse har mahine 18+ high-budget buyers direct connect huye bina ad spend waste kiye.\n\n"
                        . "Agar aap open ho, toh kya ek 2-minute ka short video walkthrough bhej doon? Zero obligation!",
                    
                    'followup_1' => "{$dmSalutationHinglish},\n\n"
                        . "Pichhle message ke baare me follow-up kar raha tha.\n\n"
                        . "Humne ek short 90-second case study banayi hai jo dikhati hai ki kaise {$city} ke luxury buyers ko direct WhatsApp aur consult par convert kiya jaata hai.\n\n"
                        . "Kya main private link yahan share kar doon?",
                    
                    'followup_2' => "Quick bump {$dmFirstName} ji — message miss na ho gaya ho isliye ping kiya!\n\n"
                        . "Kya aap abhi {$businessName} ke client pipeline ko scale karne ke liye time nikaal sakte hain, ya abhi pause par hai?",
                    
                    'followup_3' => "{$dmFirstName} ji, lagta hai aap abhi existing clients me kaafi busy hain (which is totally great!).\n\n"
                        . "Main aage message karke disturb nahi karunga. Kabhi bhi {$businessName} ke liye direct client automation dekhna ho toh ping kar dijiyega.\n\n"
                        . "Aapko aur {$businessName} ko bohot bohot shubhkamnayein!",
                ],
                'linkedin' => [
                    'initial' => "{$dmSalutationHinglish},\n\n"
                        . "{$city} me {$businessName} ka continuous expansion dekh kar reach out kar raha hoon.\n\n"
                        . "Main {$senderName}, {$senderTitle} at {$myBusiness}. Hum premium {$niche} brands ko high-ticket buyers automate karne me help karte hain.\n\n"
                        . "Kya aap ek 90-second video walkthrough dekhna chahenge jisme humne bataya hai ki ye {$businessName} ke liye kaise kaam karega?",
                    
                    'followup_1' => "{$dmSalutationHinglish}, check kar raha tha agar is quarter me new client capture system test karna aapke roadmap par hai?",
                ],
                'email' => [
                    'subject' => "{$businessName} ke liye quick idea ({$city})",
                    'body' => "{$dmSalutationHinglish},\n\n"
                        . "{$city} me {$businessName} ka established market presence dekh kar direct connect kar raha hoon.\n\n"
                        . "Ek seedha sawaal: Kya aap {$businessName} ke liye har mahine 15-20 qualified high-paying client consultations autopilot par add karne me interested hain?\n\n"
                        . "Humne similar {$niche} businesses ke liye dedicated conversion workflow run kiya hai jo 30 days me verified sales leads deliver karta hai.\n\n"
                        . "Kya main ek 2-minute ka private case study share kar doon, ya aap abhi kaafi busy hain?\n\n"
                        . "Best regards,\n"
                        . "{$senderName}\n"
                        . "{$senderTitle} | {$myBusiness}\n"
                        . "WhatsApp: {$senderPhone}",
                    
                    'followup_1_subject' => "re: {$businessName} ke liye quick idea",
                    'followup_1_body' => "{$dmSalutationHinglish},\n\n"
                        . "Bas follow-up kar raha tha agar aapko mera pehla note dekhne ka time mila?\n\n"
                        . "Jab bhi aap free hon, 2-minute ki summary share karne me khushi hogi.\n\n"
                        . "Best,\n{$senderName}",
                ],
                'instagram' => [
                    'initial' => "{$dmSalutationHinglish}! {$city} me {$businessName} ka collection aur presence lajawab hai ✨\n\n"
                        . "Quick sawaal — kya is quarter me high-ticket clients aur inquiries expand karna chahte hain?\n\n"
                        . "Hum leading {$niche} brands ko direct buyers automate karne me madad karte hain.\n\n"
                        . "Bas 'YES' reply karein aur main ek 60-second breakdown video bhej dunga!",
                ],
                'playbook' => [
                    'reply_interested' => "Bohat badiya {$dmFirstName} ji! Ye raha private 2-minute video link: [Link]. Isme humne live workflow dikhaya hai. Kya is Thursday 4 PM ya Friday 11 AM par 10 minute ka quick call suit karega?",
                    'reply_pricing' => "Humare custom client engines {$pricing} ke bracket me aate hain, depending on requirements. Hamari 7-day live pilot guarantee rehti hai. Kya aap 10 min baat karne ke liye open hain taaki samajh sakein ki {$businessName} ke liye ROI fit baithta hai ya nahi?",
                    'reply_have_agency' => "Bilkul samajh sakta hoon {$dmFirstName} ji. Hum aapki existing team ya agency ko replace nahi karte — balki hamara AI system unke saath milkar 24/7 buyers ko qualify karke direct aapke pass bhejta hai. Ek baar 5 min dekhne me koi harm nahi hai!",
                    'reply_not_interested' => "Koi baat nahi {$dmFirstName} ji, direct response ke liye thanks. Future me kabhi bhi premium client pipeline automate karni ho toh zaroor batayiye ga. {$businessName} ke success ke liye best wishes!",
                    'reply_who_is_this' => "Main {$senderName} hoon, {$myBusiness} ka founder. Main {$city} ke top business leaders ko research kar raha tha aur {$businessName} ke {$dmTitle} hone ke naate seedha aapse contact kiya. Ek practical idea share karna chahta tha!",
                    'reply_book_call' => "Zabardast! Aap apne hisab se 10 min ka slot yahan book kar sakte hain: [Calendar Link]. Baat karke khushi hogi, {$dmFirstName} ji!",
                ]
            ],

            // -------------------------------------------------------------
            // 3. HINDI (हिंदी - Formal, Professional, Respectful Business Copy)
            // -------------------------------------------------------------
            'hi' => [
                'language_label' => 'Hindi (हिंदी)',
                'whatsapp' => [
                    'initial' => "{$dmSalutationHi},\n\n"
                        . "{$city} में {$businessName} की प्रतिष्ठा और उत्कृष्ट कार्य देखकर बहुत प्रसन्नता हुई।\n\n"
                        . "एक सीधा और संक्षिप्त प्रश्न: क्या आप इस तिमाही में अपने व्यवसाय के लिए नए उच्च-मूल्य (High-Ticket) ग्राहकों को जोड़ने के लिए तैयार हैं, अथवा आपकी टीम पहले से ही पूर्णतः व्यस्त है?\n\n"
                        . "हमने हाल ही में एक प्रतिष्ठित {$niche} ब्रांड के लिए एक स्वचालित ग्राहक प्रणाली स्थापित की, जिससे बिना विज्ञापन व्यर्थ किए 18 से अधिक सत्यापित ग्राहक सीधे जुड़े।\n\n"
                        . "यदि आप अनुमति दें, तो क्या मैं इसका 2 मिनट का वीडियो विवरण आपके साथ साझा करूँ?",
                    
                    'followup_1' => "{$dmSalutationHi},\n\n"
                        . "{$businessName} के व्यवसाय विस्तार संबंधी अपने पिछले संदेश के संदर्भ में संपर्क कर रहा हूँ।\n\n"
                        . "हमने 90 सेकंड की एक संक्षिप्त रूपरेखा तैयार की है, जो दर्शाती है कि {$city} के प्रीमियम खरीदार सीधे WhatsApp पर कैसे संपर्क करते हैं।\n\n"
                        . "क्या मैं वह विवरण यहाँ प्रेषित करूँ?",
                    
                    'followup_2' => "नमस्ते {$dmFirstName} जी, आशा है आप सकुशल हैं।\n\n"
                        . "क्या आप इस समय {$businessName} के ग्राहक अधिग्रहण को स्वचालित करने के विषय पर विचार करने हेतु उपलब्ध हैं?",
                    
                    'followup_3' => "{$dmFirstName} जी, मैं समझ सकता हूँ कि आप इस समय अत्यंत महत्वपूर्ण कार्यों में व्यस्त हैं।\n\n"
                        . "मैं पुनः संदेश भेजकर आपका समय नहीं लूँगा। भविष्य में जब भी आप {$businessName} के लिए आधुनिक ग्राहक प्रणाली देखना चाहें, कृपया संपर्क करें।\n\n"
                        . "आपके और {$businessName} की निरंतर प्रगति हेतु हार्दिक शुभकामनाएँ!",
                ],
                'linkedin' => [
                    'initial' => "{$dmSalutationHi},\n\n"
                        . "{$city} में {$businessName} के उत्कृष्ट नेतृत्व और सफलता को देखकर आपसे संपर्क कर रहा हूँ।\n\n"
                        . "मैं {$senderName}, {$myBusiness} का संस्थापक हूँ। हम प्रतिष्ठित {$niche} व्यवसाय प्रमुखों को सीधे उच्च-स्तरीय ग्राहक प्राप्त करने में सहायता करते हैं।\n\n"
                        . "क्या आप 90 सेकंड का संक्षिप्त वीडियो देखना पसंद करेंगे?",
                    
                    'followup_1' => "{$dmSalutationHi}, क्या इस तिमाही में आधुनिक ग्राहक अधिग्रहण प्रणाली पर चर्चा आपके लिए उपयुक्त रहेगी?",
                ],
                'email' => [
                    'subject' => "{$businessName} के व्यवसाय विस्तार हेतु एक विचार",
                    'body' => "{$dmSalutationHi},\n\n"
                        . "{$city} में {$businessName} की उत्कृष्ट व्यावसायिक छवि देखकर आपसे सीधे संपर्क साध रहा हूँ।\n\n"
                        . "एक सीधा प्रश्न: क्या आप {$businessName} के लिए प्रतिमाह 15-20 सत्यापित उच्च-मूल्य ग्राहक परामर्श स्वचालित रूप से जोड़ने के इच्छुक हैं?\n\n"
                        . "हमने लक्ज़री {$niche} क्षेत्र के लिए एक विशेष प्रणाली तैयार की है, जो उच्च क्षमता वाले ग्राहकों को सीधे संपर्क में लाती है।\n\n"
                        . "क्या मैं 2 मिनट का संक्षिप्त विवरण आपके साथ साझा कर सकता हूँ?\n\n"
                        . "सादर,\n"
                        . "{$senderName}\n"
                        . "{$senderTitle} | {$myBusiness}\n"
                        . "दूरभाष: {$senderPhone}",
                    
                    'followup_1_subject' => "पुनश्च: {$businessName} के व्यवसाय विस्तार हेतु एक विचार",
                    'followup_1_body' => "{$dmSalutationHi},\n\n"
                        . "आशा है आपको मेरा पूर्व संदेश प्राप्त हुआ होगा।\n\n"
                        . "जब भी आपके पास 2 मिनट का समय हो, मुझे यह संक्षिप्त केस स्टडी साझा करने में प्रसन्नता होगी।\n\n"
                        . "सधन्यवाद,\n{$senderName}",
                ],
                'instagram' => [
                    'initial' => "{$dmSalutationHi}! {$city} में {$businessName} का कार्य अत्यंत सराहनीय है ✨\n\n"
                        . "एक सीधा प्रश्न — क्या आप इस तिमाही में नए प्रीमियम ग्राहकों की संख्या बढ़ाना चाहते हैं?\n\n"
                        . "हम प्रमुख {$niche} ब्रांड्स को स्वचालित ग्राहक प्रणाली प्रदान करते हैं।\n\n"
                        . "कृपया 'हाँ' लिखकर उत्तर दें, मैं 60 सेकंड का वीडियो विवरण प्रेषित कर दूँगा!",
                ],
                'playbook' => [
                    'reply_interested' => "बहुत धन्यवाद {$dmFirstName} जी! यह रहा 2 मिनट का वीडियो लिंक: [Link]। इसमें पूरी कार्यप्रणाली स्पष्ट की गई है। क्या इस गुरुवार शाम 4 बजे अथवा शुक्रवार सुबह 11 बजे 10 मिनट की परिचर्चा संभव होगी?",
                    'reply_pricing' => "हमारी प्रणाली का सामान्य निवेश {$pricing} के मध्य रहता है। हम 7 दिनों में लाइव प्रणाली की पूर्ण गारंटी देते हैं। क्या हम 10 मिनट के कॉल पर यह समझ सकते हैं कि यह {$businessName} के लिए कितना लाभकारी रहेगा?",
                    'reply_have_agency' => "मैं आपकी स्थिति का पूर्ण सम्मान करता हूँ {$dmFirstName} जी। हम आपकी वर्तमान टीम को हटाते नहीं, बल्कि उनके कार्य को गति देने हेतु 24 घंटे स्वचालित ग्राहक फ़िल्टर प्रणाली प्रदान करते हैं।",
                    'reply_not_interested' => "स्पष्ट उत्तर के लिए धन्यवाद {$dmFirstName} जी। भविष्य में जब भी आवश्यकता हो, हम सेवा के लिए तत्पर रहेंगे। आपके व्यवसाय की निरंतर प्रगति की कामना करता हूँ!",
                    'reply_who_is_this' => "मैं {$senderName} हूँ, {$myBusiness} का संस्थापक। मैंने {$city} के प्रमुख व्यवसायों का अध्ययन कर {$businessName} के {$dmTitle} के रूप में सीधे आपसे संपर्क किया है।",
                    'reply_book_call' => "उत्तम! आप अपनी सुविधानुसार यहाँ 10 मिनट का समय निर्धारित कर सकते हैं: [Calendar Link]। आपसे वार्ता की प्रतीक्षा रहेगी!",
                ]
            ],

            // -------------------------------------------------------------
            // 4. MARATHI (मराठी - Professional, Respectful, Culturally Grounded)
            // -------------------------------------------------------------
            'mr' => [
                'language_label' => 'Marathi (मराठी)',
                'whatsapp' => [
                    'initial' => "{$dmSalutationMr},\n\n"
                        . "{$city} मध्ये {$businessName} चं सातत्यपूर्ण काम आणि ब्रँडची वाढ पाहून खूप आनंद झाला.\n\n"
                        . "एक थेट आणि महत्त्वाचा प्रश्न: या तिमाहीत {$businessName} साठी नवीन हाय-तिकीट (प्रीमियम) ग्राहक जोडण्यास आपण तयार आहात का, की आपली टीम आधीच पूर्णपणे व्यस्त आहे?\n\n"
                        . "आम्ही नुकतीच एका नामांकित {$niche} ब्रँडसाठी एक थेट ग्राहक प्रणाली सुरू केली, ज्यातून जाहिरातींवर विनाकारण खर्च न करता दरमहा १८ हून अधिक खात्रीशीर ग्राहक जोडले गेले.\n\n"
                        . "आपण अनुमती दिल्यास, मी याबद्दलचा २ मिनिटांचा माहितीपूर्ण व्हिडिओ आपल्यासोबत शेअर करू का?",
                    
                    'followup_1' => "{$dmSalutationMr},\n\n"
                        . "{$businessName} च्या व्यवसाय वृद्धीसंदर्भातील आधीच्या संदेशाबाबत विचारणा करत आहे.\n\n"
                        . "आम्ही एक ९० सेकंदांचा संक्षिप्त व्हिडिओ तयार केला आहे, ज्यामध्ये {$city} मधील दर्जेदार ग्राहक थेट WhatsApp वर कसे जोडले जातात हे दाखवले आहे.\n\n"
                        . "मी लिंक येथे पाठवू का?",
                    
                    'followup_2' => "नमस्कार {$dmFirstName} जी,\n\n"
                        . "आपला संदेश चुकून राहून गेला नसेल ना, म्हणून विचारणा केली. आपण {$businessName} च्या ग्राहक संपादन प्रक्रियेवर चर्चा करण्यास उपलब्ध आहात का?",
                    
                    'followup_3' => "{$dmFirstName} जी, मला पूर्ण कल्पना आहे की आपण सध्या अत्यंत महत्त्वाच्या कामांत व्यस्त असाल.\n\n"
                        . "मी पुन्हा संदेश पाठवून आपला वेळ घेणार नाही. भविष्यात कधीही {$businessName} साठी ऑटोमेटेड सिस्टीम पाहायची असल्यास नक्की संपर्क साधा.\n\n"
                        . "आपल्याला आणि {$businessName} ला पुढील वाटचालीस मनःपूर्वक शुभेच्छा!",
                ],
                'linkedin' => [
                    'initial' => "{$dmSalutationMr},\n\n"
                        . "{$city} मध्ये {$businessName} चा विस्तार आणि आपले नेतृत्व पाहून थेट संपर्क साधत आहे.\n\n"
                        . "मी {$senderName}, {$myBusiness} चा संस्थापक. आम्ही प्रतिष्ठित {$niche} ब्रँड्सना थेट उच्च क्षमतेचे ग्राहक मिळवून देण्यास मदत करतो.\n\n"
                        . "आपण याबद्दलचा ९० सेकंदांचा व्हिडिओ पाहण्यास उत्सुक आहात का?",
                    
                    'followup_1' => "{$dmSalutationMr}, या तिमाहीत आधुनिक ग्राहक प्रणालीवर १० मिनिटे चर्चा करणे आपल्यासाठी सोयीचे ठरेल का?",
                ],
                'email' => [
                    'subject' => "{$businessName} च्या व्यवसाय वृद्धीसाठी एक कल्पना",
                    'body' => "{$dmSalutationMr},\n\n"
                        . "{$city} मध्ये {$businessName} ची प्रस्थापित प्रतिष्ठा पाहून थेट आपल्याशी संपर्क साधत आहे.\n\n"
                        . "एक थेट प्रश्न: दरमहा १५-२० दर्जेदार आणि उच्च क्षमतेच्या ग्राहकांच्या भेटीगाठी आपल्यासाठी ऑटोपायलटवर सुरू करण्यास आपण इच्छुक आहात का?\n\n"
                        . "आम्ही एका नामांकित {$niche} ब्रँडसाठी ही प्रणाली राबवली असून ३० दिवसांत त्यांचे थेट संपर्क ३ पटीने वाढले आहेत.\n\n"
                        . "आपण अनुमती दिल्यास, मी २ मिनिटांची रूपरेषा आपल्यासोबत शेअर करू इच्छितो.\n\n"
                        . "आपला नम्र,\n"
                        . "{$senderName}\n"
                        . "{$senderTitle} | {$myBusiness}\n"
                        . "दूरध्वनी: {$senderPhone}",
                    
                    'followup_1_subject' => "पुन्हा: {$businessName} च्या व्यवसाय वृद्धीसाठी एक कल्पना",
                    'followup_1_body' => "{$dmSalutationMr},\n\n"
                        . "माझा आधीचा संदेश आपल्याला मिळाला असेल अशी आशा आहे.\n\n"
                        . "आपल्या सोयीनुसार २ मिनिटांचा सारांश शेअर करण्यास मला नक्कीच आवडेल.\n\n"
                        . "धन्यवाद,\n{$senderName}",
                ],
                'instagram' => [
                    'initial' => "{$dmSalutationMr}! {$city} मध्ये {$businessName} चे काम अतिशय दर्जेदार आहे ✨\n\n"
                        . "एक थेट प्रश्न — या तिमाहीत प्रीमियम ग्राहकांची संख्या वाढवण्यास आपण उत्सुक आहात का?\n\n"
                        . "आम्ही नामांकित {$niche} ब्रँड्सना ऑटोमेटेड ग्राहक प्रणाली प्रदान करतो.\n\n"
                        . "फक्त 'हो' लिहून उत्तर द्या, मी ६० सेकंदांचा व्हिडिओ पाठवून देतो!",
                ],
                'playbook' => [
                    'reply_interested' => "खूप धन्यवाद {$dmFirstName} जी! हा २ मिनिटांचा व्हिडिओ येथे पहा: [Link]. यात संपूर्ण कार्यपद्धती दाखवली आहे. या गुरुवारी दुपारी ४ वाजता किंवा शुक्रवारी सकाळी ११ वाजता १० मिनिटांच्या संवादासाठी वेळ मिळू शकेल का?",
                    'reply_pricing' => "आमच्या सिस्टीमची सामान्य गुंतवणूक {$pricing} च्या दरम्यान असते. आम्ही ७ दिवसांत थेट परिणाम देण्याची हमी देतो. हे {$businessName} साठी फायदेशीर ठरेल का, हे तपासण्यासाठी १० मिनिटे चर्चा करणे सोयीचे होईल का?",
                    'reply_have_agency' => "आपल्या मताचा पूर्ण आदर आहे {$dmFirstName} जी. आम्ही आपल्या टीमला बदलत नाही, तर त्यांना २४ तास ऑटोमेशनची मदत देऊन दर्जेदार ग्राहक थेट त्यांच्याकडे पोहोचवतो.",
                    'reply_not_interested' => "थेट उत्तरासाठी मनापासून आभार {$dmFirstName} जी. भविष्यात कधीही गरज भासल्यास नक्की संपर्क साधा. आपल्या व्यवसायाला खूप खूप शुभेच्छा!",
                    'reply_who_is_this' => "मी {$senderName}, {$myBusiness} चा संस्थापक. {$city} मधील अग्रगण्य ब्रँड्सचा अभ्यास करताना {$businessName} चे {$dmTitle} म्हणून थेट आपल्याशी संपर्क साधला आहे.",
                    'reply_book_call' => "उत्तम! आपल्या सोयीनुसार १० मिनिटांची वेळ येथे निश्चित करू शकता: [Calendar Link]. आपल्याशी बोलण्यास उत्सुक आहे!",
                ]
            ],
        ];

        // Active language pack
        $activeLang = isset($sequences[$lang]) ? $lang : 'en';
        $activePack = $sequences[$activeLang];

        return [
            'lead_id' => $lead['id'] ?? null,
            'business_name' => $businessName,
            'decision_maker_name' => $dmFullName ?: 'Founder & Managing Director',
            'decision_maker_title' => $dmTitle,
            'city' => $city,
            'active_language' => $activeLang,
            'available_languages' => [
                'en' => 'English',
                'hinglish' => 'Hinglish (Conversational)',
                'hi' => 'Hindi (हिंदी)',
                'mr' => 'Marathi (मराठी)',
            ],
            'whatsapp' => $activePack['whatsapp'],
            'linkedin' => $activePack['linkedin'],
            'email' => $activePack['email'],
            'instagram' => $activePack['instagram'],
            'playbook' => $activePack['playbook'],
            'all_languages' => $sequences,
        ];
    }
}
