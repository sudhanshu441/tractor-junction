<?php

/*
| India: 28 states + 8 union territories, with their two-letter vehicle-registration
| style codes. This list is authoritative and stable.
|
| Districts below are STARTER data for the eight highest-volume agri states so the
| dependent selects and geo filters are usable from day one. They are NOT the full
| ~780-district national list. Load the authoritative dataset with:
|
|     php artisan geo:import path/to/districts.csv
|
| (CSV columns: state_code,district_name) — see App\Console\Commands\ImportGeography.
*/

return [
    'states' => [
        ['name' => 'Andhra Pradesh', 'code' => 'AP'],
        ['name' => 'Arunachal Pradesh', 'code' => 'AR'],
        ['name' => 'Assam', 'code' => 'AS'],
        ['name' => 'Bihar', 'code' => 'BR'],
        ['name' => 'Chhattisgarh', 'code' => 'CG'],
        ['name' => 'Goa', 'code' => 'GA'],
        ['name' => 'Gujarat', 'code' => 'GJ'],
        ['name' => 'Haryana', 'code' => 'HR'],
        ['name' => 'Himachal Pradesh', 'code' => 'HP'],
        ['name' => 'Jharkhand', 'code' => 'JH'],
        ['name' => 'Karnataka', 'code' => 'KA'],
        ['name' => 'Kerala', 'code' => 'KL'],
        ['name' => 'Madhya Pradesh', 'code' => 'MP'],
        ['name' => 'Maharashtra', 'code' => 'MH'],
        ['name' => 'Manipur', 'code' => 'MN'],
        ['name' => 'Meghalaya', 'code' => 'ML'],
        ['name' => 'Mizoram', 'code' => 'MZ'],
        ['name' => 'Nagaland', 'code' => 'NL'],
        ['name' => 'Odisha', 'code' => 'OD'],
        ['name' => 'Punjab', 'code' => 'PB'],
        ['name' => 'Rajasthan', 'code' => 'RJ'],
        ['name' => 'Sikkim', 'code' => 'SK'],
        ['name' => 'Tamil Nadu', 'code' => 'TN'],
        ['name' => 'Telangana', 'code' => 'TS'],
        ['name' => 'Tripura', 'code' => 'TR'],
        ['name' => 'Uttar Pradesh', 'code' => 'UP'],
        ['name' => 'Uttarakhand', 'code' => 'UK'],
        ['name' => 'West Bengal', 'code' => 'WB'],
        // Union territories
        ['name' => 'Andaman and Nicobar Islands', 'code' => 'AN'],
        ['name' => 'Chandigarh', 'code' => 'CH'],
        ['name' => 'Dadra and Nagar Haveli and Daman and Diu', 'code' => 'DD'],
        ['name' => 'Delhi', 'code' => 'DL'],
        ['name' => 'Jammu and Kashmir', 'code' => 'JK'],
        ['name' => 'Ladakh', 'code' => 'LA'],
        ['name' => 'Lakshadweep', 'code' => 'LD'],
        ['name' => 'Puducherry', 'code' => 'PY'],
    ],

    // STARTER district data — verify against the authoritative source before launch.
    'districts' => [
        'UP' => [
            'Agra', 'Aligarh', 'Ambedkar Nagar', 'Amethi', 'Amroha', 'Auraiya', 'Ayodhya', 'Azamgarh',
            'Baghpat', 'Bahraich', 'Ballia', 'Balrampur', 'Banda', 'Barabanki', 'Bareilly', 'Basti',
            'Bhadohi', 'Bijnor', 'Budaun', 'Bulandshahr', 'Chandauli', 'Chitrakoot', 'Deoria', 'Etah',
            'Etawah', 'Farrukhabad', 'Fatehpur', 'Firozabad', 'Gautam Buddha Nagar', 'Ghaziabad',
            'Ghazipur', 'Gonda', 'Gorakhpur', 'Hamirpur', 'Hapur', 'Hardoi', 'Hathras', 'Jalaun',
            'Jaunpur', 'Jhansi', 'Kannauj', 'Kanpur Dehat', 'Kanpur Nagar', 'Kasganj', 'Kaushambi',
            'Kushinagar', 'Lakhimpur Kheri', 'Lalitpur', 'Lucknow', 'Maharajganj', 'Mahoba',
            'Mainpuri', 'Mathura', 'Mau', 'Meerut', 'Mirzapur', 'Moradabad', 'Muzaffarnagar', 'Pilibhit',
            'Pratapgarh', 'Prayagraj', 'Raebareli', 'Rampur', 'Saharanpur', 'Sambhal', 'Sant Kabir Nagar',
            'Shahjahanpur', 'Shamli', 'Shrawasti', 'Siddharthnagar', 'Sitapur', 'Sonbhadra', 'Sultanpur',
            'Unnao', 'Varanasi',
        ],
        'MP' => [
            'Agar Malwa', 'Alirajpur', 'Anuppur', 'Ashoknagar', 'Balaghat', 'Barwani', 'Betul', 'Bhind',
            'Bhopal', 'Burhanpur', 'Chhatarpur', 'Chhindwara', 'Damoh', 'Datia', 'Dewas', 'Dhar', 'Dindori',
            'Guna', 'Gwalior', 'Harda', 'Indore', 'Jabalpur', 'Jhabua', 'Katni', 'Khandwa', 'Khargone',
            'Mandla', 'Mandsaur', 'Morena', 'Narsinghpur', 'Neemuch', 'Niwari', 'Panna', 'Raisen',
            'Rajgarh', 'Ratlam', 'Rewa', 'Sagar', 'Satna', 'Sehore', 'Seoni', 'Shahdol', 'Shajapur',
            'Sheopur', 'Shivpuri', 'Sidhi', 'Singrauli', 'Tikamgarh', 'Ujjain', 'Umaria', 'Vidisha',
        ],
        'RJ' => [
            'Ajmer', 'Alwar', 'Banswara', 'Baran', 'Barmer', 'Bharatpur', 'Bhilwara', 'Bikaner', 'Bundi',
            'Chittorgarh', 'Churu', 'Dausa', 'Dholpur', 'Dungarpur', 'Hanumangarh', 'Jaipur', 'Jaisalmer',
            'Jalore', 'Jhalawar', 'Jhunjhunu', 'Jodhpur', 'Karauli', 'Kota', 'Nagaur', 'Pali',
            'Pratapgarh', 'Rajsamand', 'Sawai Madhopur', 'Sikar', 'Sirohi', 'Sri Ganganagar', 'Tonk', 'Udaipur',
        ],
        'MH' => [
            'Ahmednagar', 'Akola', 'Amravati', 'Aurangabad', 'Beed', 'Bhandara', 'Buldhana', 'Chandrapur',
            'Dhule', 'Gadchiroli', 'Gondia', 'Hingoli', 'Jalgaon', 'Jalna', 'Kolhapur', 'Latur',
            'Mumbai City', 'Mumbai Suburban', 'Nagpur', 'Nanded', 'Nandurbar', 'Nashik', 'Osmanabad',
            'Palghar', 'Parbhani', 'Pune', 'Raigad', 'Ratnagiri', 'Sangli', 'Satara', 'Sindhudurg',
            'Solapur', 'Thane', 'Wardha', 'Washim', 'Yavatmal',
        ],
        'PB' => [
            'Amritsar', 'Barnala', 'Bathinda', 'Faridkot', 'Fatehgarh Sahib', 'Fazilka', 'Ferozepur',
            'Gurdaspur', 'Hoshiarpur', 'Jalandhar', 'Kapurthala', 'Ludhiana', 'Mansa', 'Moga',
            'Muktsar', 'Pathankot', 'Patiala', 'Rupnagar', 'Sahibzada Ajit Singh Nagar', 'Sangrur',
            'Shahid Bhagat Singh Nagar', 'Tarn Taran',
        ],
        'HR' => [
            'Ambala', 'Bhiwani', 'Charkhi Dadri', 'Faridabad', 'Fatehabad', 'Gurugram', 'Hisar', 'Jhajjar',
            'Jind', 'Kaithal', 'Karnal', 'Kurukshetra', 'Mahendragarh', 'Nuh', 'Palwal', 'Panchkula',
            'Panipat', 'Rewari', 'Rohtak', 'Sirsa', 'Sonipat', 'Yamunanagar',
        ],
        'GJ' => [
            'Ahmedabad', 'Amreli', 'Anand', 'Aravalli', 'Banaskantha', 'Bharuch', 'Bhavnagar', 'Botad',
            'Chhota Udaipur', 'Dahod', 'Dang', 'Devbhoomi Dwarka', 'Gandhinagar', 'Gir Somnath', 'Jamnagar',
            'Junagadh', 'Kheda', 'Kutch', 'Mahisagar', 'Mehsana', 'Morbi', 'Narmada', 'Navsari',
            'Panchmahal', 'Patan', 'Porbandar', 'Rajkot', 'Sabarkantha', 'Surat', 'Surendranagar',
            'Tapi', 'Vadodara', 'Valsad',
        ],
        'BR' => [
            'Araria', 'Arwal', 'Aurangabad', 'Banka', 'Begusarai', 'Bhagalpur', 'Bhojpur', 'Buxar',
            'Darbhanga', 'East Champaran', 'Gaya', 'Gopalganj', 'Jamui', 'Jehanabad', 'Kaimur',
            'Katihar', 'Khagaria', 'Kishanganj', 'Lakhisarai', 'Madhepura', 'Madhubani', 'Munger',
            'Muzaffarpur', 'Nalanda', 'Nawada', 'Patna', 'Purnia', 'Rohtas', 'Saharsa', 'Samastipur',
            'Saran', 'Sheikhpura', 'Sheohar', 'Sitamarhi', 'Siwan', 'Supaul', 'Vaishali', 'West Champaran',
        ],
    ],
];
