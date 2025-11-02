@extends('adminlte::page')

@section('title', 'Edit proxy #' . $proxy->id)

@section('content_header')
    <h1>Edit proxy #{{ $proxy->id }}</h1>
@stop

@section('content')
    <div class="row">
        @if(session('success'))
            <div class="col-12">
                <div class="alert alert-success">{{ session('success') }}</div>
            </div>
        @endif

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Proxy data</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.proxies.update', $proxy) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select name="type" id="type" class="form-control @error('type') is-invalid @enderror">
                                <option value="HTTPS" {{ old('type', $proxy->type) == 'HTTPS' ? 'selected' : '' }}>HTTPS</option>
                                <option value="HTTP" {{ old('type', $proxy->type) == 'HTTP' ? 'selected' : '' }}>HTTP</option>
                                <option value="SOCKS4" {{ old('type', $proxy->type) == 'SOCKS4' ? 'selected' : '' }}>SOCKS4</option>
                                <option value="SOCKS5" {{ old('type', $proxy->type) == 'SOCKS5' ? 'selected' : '' }}>SOCKS5</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" name="address" id="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $proxy->address) }}">
                            @error('address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="credentials">Credentials</label>
                            <input type="text" name="credentials" id="credentials" class="form-control @error('credentials') is-invalid @enderror" value="{{ old('credentials', $proxy->credentials) }}">
                            @error('credentials')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="is_active">Status</label>
                            <select name="is_active" id="is_active" class="form-control @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', $proxy->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $proxy->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="country">Country</label>
                            <select name="country" id="country" class="form-control @error('country') is-invalid @enderror">
                                <option value="">Select country</option>
                                <option value="AF" {{ old('is_active', $proxy->country) == 'AF' ? 'selected' : '' }}>Afghanistan</option>
                                <option value="AL" {{ old('country', $proxy->country) == 'AL' ? 'selected' : '' }}>Albania</option>
                                <option value="DZ" {{ old('country', $proxy->country) == 'DZ' ? 'selected' : '' }}>Algeria</option>
                                <option value="AS" {{ old('country', $proxy->country) == 'AS' ? 'selected' : '' }}>American Samoa</option>
                                <option value="AD" {{ old('country', $proxy->country) == 'AD' ? 'selected' : '' }}>Andorra</option>
                                <option value="AO" {{ old('country', $proxy->country) == 'AO' ? 'selected' : '' }}>Angola</option>
                                <option value="AI" {{ old('country', $proxy->country) == 'AI' ? 'selected' : '' }}>Anguilla</option>
                                <option value="AG" {{ old('country', $proxy->country) == 'AG' ? 'selected' : '' }}>Antigua and Barbuda</option>
                                <option value="AR" {{ old('country', $proxy->country) == 'AR' ? 'selected' : '' }}>Argentina</option>
                                <option value="AM" {{ old('country', $proxy->country) == 'AM' ? 'selected' : '' }}>Armenia</option>
                                <option value="AW" {{ old('country', $proxy->country) == 'AW' ? 'selected' : '' }}>Aruba</option>
                                <option value="AU" {{ old('country', $proxy->country) == 'AU' ? 'selected' : '' }}>Australia</option>
                                <option value="AT" {{ old('country', $proxy->country) == 'AT' ? 'selected' : '' }}>Austria</option>
                                <option value="AZ" {{ old('country', $proxy->country) == 'AZ' ? 'selected' : '' }}>Azerbaijan</option>
                                <option value="BS" {{ old('country', $proxy->country) == 'BS' ? 'selected' : '' }}>Bahamas</option>
                                <option value="BH" {{ old('country', $proxy->country) == 'BH' ? 'selected' : '' }}>Bahrain</option>
                                <option value="BD" {{ old('country', $proxy->country) == 'BD' ? 'selected' : '' }}>Bangladesh</option>
                                <option value="BB" {{ old('country', $proxy->country) == 'BB' ? 'selected' : '' }}>Barbados</option>
                                <option value="BY" {{ old('country', $proxy->country) == 'BY' ? 'selected' : '' }}>Belarus</option>
                                <option value="BE" {{ old('country', $proxy->country) == 'BE' ? 'selected' : '' }}>Belgium</option>
                                <option value="BZ" {{ old('country', $proxy->country) == 'BZ' ? 'selected' : '' }}>Belize</option>
                                <option value="BJ" {{ old('country', $proxy->country) == 'BJ' ? 'selected' : '' }}>Benin</option>
                                <option value="BM" {{ old('country', $proxy->country) == 'BM' ? 'selected' : '' }}>Bermuda</option>
                                <option value="BT" {{ old('country', $proxy->country) == 'BT' ? 'selected' : '' }}>Bhutan</option>
                                <option value="BO" {{ old('country', $proxy->country) == 'BO' ? 'selected' : '' }}>Bolivia</option>
                                <option value="BA" {{ old('country', $proxy->country) == 'BA' ? 'selected' : '' }}>Bosnia and Herzegovina</option>
                                <option value="BW" {{ old('country', $proxy->country) == 'BW' ? 'selected' : '' }}>Botswana</option>
                                <option value="BR" {{ old('country', $proxy->country) == 'BR' ? 'selected' : '' }}>Brazil</option>
                                <option value="BN" {{ old('country', $proxy->country) == 'BN' ? 'selected' : '' }}>Brunei</option>
                                <option value="BG" {{ old('country', $proxy->country) == 'BG' ? 'selected' : '' }}>Bulgaria</option>
                                <option value="BF" {{ old('country', $proxy->country) == 'BF' ? 'selected' : '' }}>Burkina Faso</option>
                                <option value="BI" {{ old('country', $proxy->country) == 'BI' ? 'selected' : '' }}>Burundi</option>
                                <option value="KH" {{ old('country', $proxy->country) == 'KH' ? 'selected' : '' }}>Cambodia</option>
                                <option value="CM" {{ old('country', $proxy->country) == 'CM' ? 'selected' : '' }}>Cameroon</option>
                                <option value="CA" {{ old('country', $proxy->country) == 'CA' ? 'selected' : '' }}>Canada</option>
                                <option value="CV" {{ old('country', $proxy->country) == 'CV' ? 'selected' : '' }}>Cape Verde</option>
                                <option value="KY" {{ old('country', $proxy->country) == 'KY' ? 'selected' : '' }}>Cayman Islands</option>
                                <option value="CF" {{ old('country', $proxy->country) == 'CF' ? 'selected' : '' }}>Central African Republic</option>
                                <option value="TD" {{ old('country', $proxy->country) == 'TD' ? 'selected' : '' }}>Chad</option>
                                <option value="CL" {{ old('country', $proxy->country) == 'CL' ? 'selected' : '' }}>Chile</option>
                                <option value="CN" {{ old('country', $proxy->country) == 'CN' ? 'selected' : '' }}>China</option>
                                <option value="CO" {{ old('country', $proxy->country) == 'CO' ? 'selected' : '' }}>Colombia</option>
                                <option value="KM" {{ old('country', $proxy->country) == 'KM' ? 'selected' : '' }}>Comoros</option>
                                <option value="CG" {{ old('country', $proxy->country) == 'CG' ? 'selected' : '' }}>Congo</option>
                                <option value="CD" {{ old('country', $proxy->country) == 'CD' ? 'selected' : '' }}>Democratic Republic of the Congo</option>
                                <option value="CR" {{ old('country', $proxy->country) == 'CR' ? 'selected' : '' }}>Costa Rica</option>
                                <option value="HR" {{ old('country', $proxy->country) == 'HR' ? 'selected' : '' }}>Croatia</option>
                                <option value="CU" {{ old('country', $proxy->country) == 'CU' ? 'selected' : '' }}>Cuba</option>
                                <option value="CY" {{ old('country', $proxy->country) == 'CY' ? 'selected' : '' }}>Cyprus</option>
                                <option value="CZ" {{ old('country', $proxy->country) == 'CZ' ? 'selected' : '' }}>Czech Republic</option>
                                <option value="DK" {{ old('country', $proxy->country) == 'DK' ? 'selected' : '' }}>Denmark</option>
                                <option value="DJ" {{ old('country', $proxy->country) == 'DJ' ? 'selected' : '' }}>Djibouti</option>
                                <option value="DM" {{ old('country', $proxy->country) == 'DM' ? 'selected' : '' }}>Dominica</option>
                                <option value="DO" {{ old('country', $proxy->country) == 'DO' ? 'selected' : '' }}>Dominican Republic</option>
                                <option value="EC" {{ old('country', $proxy->country) == 'EC' ? 'selected' : '' }}>Ecuador</option>
                                <option value="EG" {{ old('country', $proxy->country) == 'EG' ? 'selected' : '' }}>Egypt</option>
                                <option value="SV" {{ old('country', $proxy->country) == 'SV' ? 'selected' : '' }}>El Salvador</option>
                                <option value="GQ" {{ old('country', $proxy->country) == 'GQ' ? 'selected' : '' }}>Equatorial Guinea</option>
                                <option value="ER" {{ old('country', $proxy->country) == 'ER' ? 'selected' : '' }}>Eritrea</option>
                                <option value="EE" {{ old('country', $proxy->country) == 'EE' ? 'selected' : '' }}>Estonia</option>
                                <option value="ET" {{ old('country', $proxy->country) == 'ET' ? 'selected' : '' }}>Ethiopia</option>
                                <option value="FO" {{ old('country', $proxy->country) == 'FO' ? 'selected' : '' }}>Faroe Islands</option>
                                <option value="FJ" {{ old('country', $proxy->country) == 'FJ' ? 'selected' : '' }}>Fiji</option>
                                <option value="FI" {{ old('country', $proxy->country) == 'FI' ? 'selected' : '' }}>Finland</option>
                                <option value="FR" {{ old('country', $proxy->country) == 'FR' ? 'selected' : '' }}>France</option>
                                <option value="GF" {{ old('country', $proxy->country) == 'GF' ? 'selected' : '' }}>French Guiana</option>
                                <option value="PF" {{ old('country', $proxy->country) == 'PF' ? 'selected' : '' }}>French Polynesia</option>
                                <option value="GA" {{ old('country', $proxy->country) == 'GA' ? 'selected' : '' }}>Gabon</option>
                                <option value="GM" {{ old('country', $proxy->country) == 'GM' ? 'selected' : '' }}>Gambia</option>
                                <option value="GE" {{ old('country', $proxy->country) == 'GE' ? 'selected' : '' }}>Georgia</option>
                                <option value="DE" {{ old('country', $proxy->country) == 'DE' ? 'selected' : '' }}>Germany</option>
                                <option value="GH" {{ old('country', $proxy->country) == 'GH' ? 'selected' : '' }}>Ghana</option>
                                <option value="GI" {{ old('country', $proxy->country) == 'GI' ? 'selected' : '' }}>Gibraltar</option>
                                <option value="GR" {{ old('country', $proxy->country) == 'GR' ? 'selected' : '' }}>Greece</option>
                                <option value="GL" {{ old('country', $proxy->country) == 'GL' ? 'selected' : '' }}>Greenland</option>
                                <option value="GD" {{ old('country', $proxy->country) == 'GD' ? 'selected' : '' }}>Grenada</option>
                                <option value="GU" {{ old('country', $proxy->country) == 'GU' ? 'selected' : '' }}>Guam</option>
                                <option value="GT" {{ old('country', $proxy->country) == 'GT' ? 'selected' : '' }}>Guatemala</option>
                                <option value="GG" {{ old('country', $proxy->country) == 'GG' ? 'selected' : '' }}>Guernsey</option>
                                <option value="GN" {{ old('country', $proxy->country) == 'GN' ? 'selected' : '' }}>Guinea</option>
                                <option value="GW" {{ old('country', $proxy->country) == 'GW' ? 'selected' : '' }}>Guinea-Bissau</option>
                                <option value="GY" {{ old('country', $proxy->country) == 'GY' ? 'selected' : '' }}>Guyana</option>
                                <option value="HT" {{ old('country', $proxy->country) == 'HT' ? 'selected' : '' }}>Haiti</option>
                                <option value="HN" {{ old('country', $proxy->country) == 'HN' ? 'selected' : '' }}>Honduras</option>
                                <option value="HK" {{ old('country', $proxy->country) == 'HK' ? 'selected' : '' }}>Hong Kong</option>
                                <option value="HU" {{ old('country', $proxy->country) == 'HU' ? 'selected' : '' }}>Hungary</option>
                                <option value="IS" {{ old('country', $proxy->country) == 'IS' ? 'selected' : '' }}>Iceland</option>
                                <option value="IN" {{ old('country', $proxy->country) == 'IN' ? 'selected' : '' }}>India</option>
                                <option value="ID" {{ old('country', $proxy->country) == 'ID' ? 'selected' : '' }}>Indonesia</option>
                                <option value="IR" {{ old('country', $proxy->country) == 'IR' ? 'selected' : '' }}>Iran</option>
                                <option value="IQ" {{ old('country', $proxy->country) == 'IQ' ? 'selected' : '' }}>Iraq</option>
                                <option value="IE" {{ old('country', $proxy->country) == 'IE' ? 'selected' : '' }}>Ireland</option>
                                <option value="IL" {{ old('country', $proxy->country) == 'IL' ? 'selected' : '' }}>Israel</option>
                                <option value="IT" {{ old('country', $proxy->country) == 'IT' ? 'selected' : '' }}>Italy</option>
                                <option value="CI" {{ old('country', $proxy->country) == 'CI' ? 'selected' : '' }}>Ivory Coast</option>
                                <option value="JM" {{ old('country', $proxy->country) == 'JM' ? 'selected' : '' }}>Jamaica</option>
                                <option value="JP" {{ old('country', $proxy->country) == 'JP' ? 'selected' : '' }}>Japan</option>
                                <option value="JE" {{ old('country', $proxy->country) == 'JE' ? 'selected' : '' }}>Jersey</option>
                                <option value="JO" {{ old('country', $proxy->country) == 'JO' ? 'selected' : '' }}>Jordan</option>
                                <option value="KZ" {{ old('country', $proxy->country) == 'KZ' ? 'selected' : '' }}>Kazakhstan</option>
                                <option value="KE" {{ old('country', $proxy->country) == 'KE' ? 'selected' : '' }}>Kenya</option>
                                <option value="KI" {{ old('country', $proxy->country) == 'KI' ? 'selected' : '' }}>Kiribati</option>
                                <option value="KW" {{ old('country', $proxy->country) == 'KW' ? 'selected' : '' }}>Kuwait</option>
                                <option value="KG" {{ old('country', $proxy->country) == 'KG' ? 'selected' : '' }}>Kyrgyzstan</option>
                                <option value="LA" {{ old('country', $proxy->country) == 'LA' ? 'selected' : '' }}>Laos</option>
                                <option value="LV" {{ old('country', $proxy->country) == 'LV' ? 'selected' : '' }}>Latvia</option>
                                <option value="LB" {{ old('country', $proxy->country) == 'LB' ? 'selected' : '' }}>Lebanon</option>
                                <option value="LS" {{ old('country', $proxy->country) == 'LS' ? 'selected' : '' }}>Lesotho</option>
                                <option value="LR" {{ old('country', $proxy->country) == 'LR' ? 'selected' : '' }}>Liberia</option>
                                <option value="LY" {{ old('country', $proxy->country) == 'LY' ? 'selected' : '' }}>Libya</option>
                                <option value="LI" {{ old('country', $proxy->country) == 'LI' ? 'selected' : '' }}>Liechtenstein</option>
                                <option value="LT" {{ old('country', $proxy->country) == 'LT' ? 'selected' : '' }}>Lithuania</option>
                                <option value="LU" {{ old('country', $proxy->country) == 'LU' ? 'selected' : '' }}>Luxembourg</option>
                                <option value="MO" {{ old('country', $proxy->country) == 'MO' ? 'selected' : '' }}>Macao</option>
                                <option value="MK" {{ old('country', $proxy->country) == 'MK' ? 'selected' : '' }}>Macedonia</option>
                                <option value="MG" {{ old('country', $proxy->country) == 'MG' ? 'selected' : '' }}>Madagascar</option>
                                <option value="MW" {{ old('country', $proxy->country) == 'MW' ? 'selected' : '' }}>Malawi</option>
                                <option value="MY" {{ old('country', $proxy->country) == 'MY' ? 'selected' : '' }}>Malaysia</option>
                                <option value="MV" {{ old('country', $proxy->country) == 'MV' ? 'selected' : '' }}>Maldives</option>
                                <option value="ML" {{ old('country', $proxy->country) == 'ML' ? 'selected' : '' }}>Mali</option>
                                <option value="MT" {{ old('country', $proxy->country) == 'MT' ? 'selected' : '' }}>Malta</option>
                                <option value="MH" {{ old('country', $proxy->country) == 'MH' ? 'selected' : '' }}>Marshall Islands</option>
                                <option value="MQ" {{ old('country', $proxy->country) == 'MQ' ? 'selected' : '' }}>Martinique</option>
                                <option value="MR" {{ old('country', $proxy->country) == 'MR' ? 'selected' : '' }}>Mauritania</option>
                                <option value="MU" {{ old('country', $proxy->country) == 'MU' ? 'selected' : '' }}>Mauritius</option>
                                <option value="YT" {{ old('country', $proxy->country) == 'YT' ? 'selected' : '' }}>Mayotte</option>
                                <option value="MX" {{ old('country', $proxy->country) == 'MX' ? 'selected' : '' }}>Mexico</option>
                                <option value="FM" {{ old('country', $proxy->country) == 'FM' ? 'selected' : '' }}>Micronesia</option>
                                <option value="MD" {{ old('country', $proxy->country) == 'MD' ? 'selected' : '' }}>Moldova</option>
                                <option value="MC" {{ old('country', $proxy->country) == 'MC' ? 'selected' : '' }}>Monaco</option>
                                <option value="MN" {{ old('country', $proxy->country) == 'MN' ? 'selected' : '' }}>Mongolia</option>
                                <option value="ME" {{ old('country', $proxy->country) == 'ME' ? 'selected' : '' }}>Montenegro</option>
                                <option value="MS" {{ old('country', $proxy->country) == 'MS' ? 'selected' : '' }}>Montserrat</option>
                                <option value="MA" {{ old('country', $proxy->country) == 'MA' ? 'selected' : '' }}>Morocco</option>
                                <option value="MZ" {{ old('country', $proxy->country) == 'MZ' ? 'selected' : '' }}>Mozambique</option>
                                <option value="MM" {{ old('country', $proxy->country) == 'MM' ? 'selected' : '' }}>Myanmar</option>
                                <option value="NA" {{ old('country', $proxy->country) == 'NA' ? 'selected' : '' }}>Namibia</option>
                                <option value="NR" {{ old('country', $proxy->country) == 'NR' ? 'selected' : '' }}>Nauru</option>
                                <option value="NP" {{ old('country', $proxy->country) == 'NP' ? 'selected' : '' }}>Nepal</option>
                                <option value="NL" {{ old('country', $proxy->country) == 'NL' ? 'selected' : '' }}>Netherlands</option>
                                <option value="NC" {{ old('country', $proxy->country) == 'NC' ? 'selected' : '' }}>New Caledonia</option>
                                <option value="NZ" {{ old('country', $proxy->country) == 'NZ' ? 'selected' : '' }}>New Zealand</option>
                                <option value="NI" {{ old('country', $proxy->country) == 'NI' ? 'selected' : '' }}>Nicaragua</option>
                                <option value="NE" {{ old('country', $proxy->country) == 'NE' ? 'selected' : '' }}>Niger</option>
                                <option value="NG" {{ old('country', $proxy->country) == 'NG' ? 'selected' : '' }}>Nigeria</option>
                                <option value="NU" {{ old('country', $proxy->country) == 'NU' ? 'selected' : '' }}>Niue</option>
                                <option value="NF" {{ old('country', $proxy->country) == 'NF' ? 'selected' : '' }}>Norfolk Island</option>
                                <option value="KP" {{ old('country', $proxy->country) == 'KP' ? 'selected' : '' }}>North Korea</option>
                                <option value="MP" {{ old('country', $proxy->country) == 'MP' ? 'selected' : '' }}>Northern Mariana Islands</option>
                                <option value="NO" {{ old('country', $proxy->country) == 'NO' ? 'selected' : '' }}>Norway</option>
                                <option value="OM" {{ old('country', $proxy->country) == 'OM' ? 'selected' : '' }}>Oman</option>
                                <option value="PK" {{ old('country', $proxy->country) == 'PK' ? 'selected' : '' }}>Pakistan</option>
                                <option value="PW" {{ old('country', $proxy->country) == 'PW' ? 'selected' : '' }}>Palau</option>
                                <option value="PS" {{ old('country', $proxy->country) == 'PS' ? 'selected' : '' }}>Palestine</option>
                                <option value="PA" {{ old('country', $proxy->country) == 'PA' ? 'selected' : '' }}>Panama</option>
                                <option value="PG" {{ old('country', $proxy->country) == 'PG' ? 'selected' : '' }}>Papua New Guinea</option>
                                <option value="PY" {{ old('country', $proxy->country) == 'PY' ? 'selected' : '' }}>Paraguay</option>
                                <option value="PE" {{ old('country', $proxy->country) == 'PE' ? 'selected' : '' }}>Peru</option>
                                <option value="PH" {{ old('country', $proxy->country) == 'PH' ? 'selected' : '' }}>Philippines</option>
                                <option value="PN" {{ old('country', $proxy->country) == 'PN' ? 'selected' : '' }}>Pitcairn Islands</option>
                                <option value="PL" {{ old('country', $proxy->country) == 'PL' ? 'selected' : '' }}>Poland</option>
                                <option value="PT" {{ old('country', $proxy->country) == 'PT' ? 'selected' : '' }}>Portugal</option>
                                <option value="PR" {{ old('country', $proxy->country) == 'PR' ? 'selected' : '' }}>Puerto Rico</option>
                                <option value="QA" {{ old('country', $proxy->country) == 'QA' ? 'selected' : '' }}>Qatar</option>
                                <option value="RE" {{ old('country', $proxy->country) == 'RE' ? 'selected' : '' }}>Reunion</option>
                                <option value="RO" {{ old('country', $proxy->country) == 'RO' ? 'selected' : '' }}>Romania</option>
                                <option value="RU" {{ old('country', $proxy->country) == 'RU' ? 'selected' : '' }}>Russia</option>
                                <option value="RW" {{ old('country', $proxy->country) == 'RW' ? 'selected' : '' }}>Rwanda</option>
                                <option value="BL" {{ old('country', $proxy->country) == 'BL' ? 'selected' : '' }}>Saint Barthelemy</option>
                                <option value="SH" {{ old('country', $proxy->country) == 'SH' ? 'selected' : '' }}>Saint Helena</option>
                                <option value="KN" {{ old('country', $proxy->country) == 'KN' ? 'selected' : '' }}>Saint Kitts and Nevis</option>
                                <option value="LC" {{ old('country', $proxy->country) == 'LC' ? 'selected' : '' }}>Saint Lucia</option>
                                <option value="MF" {{ old('country', $proxy->country) == 'MF' ? 'selected' : '' }}>Saint Martin</option>
                                <option value="PM" {{ old('country', $proxy->country) == 'PM' ? 'selected' : '' }}>Saint Pierre and Miquelon</option>
                                <option value="VC" {{ old('country', $proxy->country) == 'VC' ? 'selected' : '' }}>Saint Vincent and the Grenadines</option>
                                <option value="WS" {{ old('country', $proxy->country) == 'WS' ? 'selected' : '' }}>Samoa</option>
                                <option value="SM" {{ old('country', $proxy->country) == 'SM' ? 'selected' : '' }}>San Marino</option>
                                <option value="ST" {{ old('country', $proxy->country) == 'ST' ? 'selected' : '' }}>Sao Tome and Principe</option>
                                <option value="SA" {{ old('country', $proxy->country) == 'SA' ? 'selected' : '' }}>Saudi Arabia</option>
                                <option value="SN" {{ old('country', $proxy->country) == 'SN' ? 'selected' : '' }}>Senegal</option>
                                <option value="RS" {{ old('country', $proxy->country) == 'RS' ? 'selected' : '' }}>Serbia</option>
                                <option value="SC" {{ old('country', $proxy->country) == 'SC' ? 'selected' : '' }}>Seychelles</option>
                                <option value="SL" {{ old('country', $proxy->country) == 'SL' ? 'selected' : '' }}>Sierra Leone</option>
                                <option value="SG" {{ old('country', $proxy->country) == 'SG' ? 'selected' : '' }}>Singapore</option>
                                <option value="SX" {{ old('country', $proxy->country) == 'SX' ? 'selected' : '' }}>Sint Maarten</option>
                                <option value="SK" {{ old('country', $proxy->country) == 'SK' ? 'selected' : '' }}>Slovakia</option>
                                <option value="SI" {{ old('country', $proxy->country) == 'SI' ? 'selected' : '' }}>Slovenia</option>
                                <option value="SB" {{ old('country', $proxy->country) == 'SB' ? 'selected' : '' }}>Solomon Islands</option>
                                <option value="SO" {{ old('country', $proxy->country) == 'SO' ? 'selected' : '' }}>Somalia</option>
                                <option value="ZA" {{ old('country', $proxy->country) == 'ZA' ? 'selected' : '' }}>South Africa</option>
                                <option value="GS" {{ old('country', $proxy->country) == 'GS' ? 'selected' : '' }}>South Georgia and the South Sandwich Islands</option>
                                <option value="KR" {{ old('country', $proxy->country) == 'KR' ? 'selected' : '' }}>South Korea</option>
                                <option value="SS" {{ old('country', $proxy->country) == 'SS' ? 'selected' : '' }}>South Sudan</option>
                                <option value="ES" {{ old('country', $proxy->country) == 'ES' ? 'selected' : '' }}>Spain</option>
                                <option value="LK" {{ old('country', $proxy->country) == 'LK' ? 'selected' : '' }}>Sri Lanka</option>
                                <option value="SD" {{ old('country', $proxy->country) == 'SD' ? 'selected' : '' }}>Sudan</option>
                                <option value="SR" {{ old('country', $proxy->country) == 'SR' ? 'selected' : '' }}>Suriname</option>
                                <option value="SJ" {{ old('country', $proxy->country) == 'SJ' ? 'selected' : '' }}>Svalbard and Jan Mayen</option>
                                <option value="SE" {{ old('country', $proxy->country) == 'SE' ? 'selected' : '' }}>Sweden</option>
                                <option value="CH" {{ old('country', $proxy->country) == 'CH' ? 'selected' : '' }}>Switzerland</option>
                                <option value="SY" {{ old('country', $proxy->country) == 'SY' ? 'selected' : '' }}>Syria</option>
                                <option value="TW" {{ old('country', $proxy->country) == 'TW' ? 'selected' : '' }}>Taiwan</option>
                                <option value="TJ" {{ old('country', $proxy->country) == 'TJ' ? 'selected' : '' }}>Tajikistan</option>
                                <option value="TZ" {{ old('country', $proxy->country) == 'TZ' ? 'selected' : '' }}>Tanzania</option>
                                <option value="TH" {{ old('country', $proxy->country) == 'TH' ? 'selected' : '' }}>Thailand</option>
                                <option value="TG" {{ old('country', $proxy->country) == 'TG' ? 'selected' : '' }}>Togo</option>
                                <option value="TK" {{ old('country', $proxy->country) == 'TK' ? 'selected' : '' }}>Tokelau</option>
                                <option value="TO" {{ old('country', $proxy->country) == 'TO' ? 'selected' : '' }}>Tonga</option>
                                <option value="TT" {{ old('country', $proxy->country) == 'TT' ? 'selected' : '' }}>Trinidad and Tobago</option>
                                <option value="TN" {{ old('country', $proxy->country) == 'TN' ? 'selected' : '' }}>Tunisia</option>
                                <option value="TR" {{ old('country', $proxy->country) == 'TR' ? 'selected' : '' }}>Turkey</option>
                                <option value="TM" {{ old('country', $proxy->country) == 'TM' ? 'selected' : '' }}>Turkmenistan</option>
                                <option value="TC" {{ old('country', $proxy->country) == 'TC' ? 'selected' : '' }}>Turks and Caicos Islands</option>
                                <option value="TV" {{ old('country', $proxy->country) == 'TV' ? 'selected' : '' }}>Tuvalu</option>
                                <option value="UG" {{ old('country', $proxy->country) == 'UG' ? 'selected' : '' }}>Uganda</option>
                                <option value="UA" {{ old('country', $proxy->country) == 'UA' ? 'selected' : '' }}>Ukraine</option>
                                <option value="AE" {{ old('country', $proxy->country) == 'AE' ? 'selected' : '' }}>United Arab Emirates</option>
                                <option value="GB" {{ old('country', $proxy->country) == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                <option value="US" {{ old('country', $proxy->country) == 'US' ? 'selected' : '' }}>United States</option>
                                <option value="UY" {{ old('country', $proxy->country) == 'UY' ? 'selected' : '' }}>Uruguay</option>
                                <option value="UZ" {{ old('country', $proxy->country) == 'UZ' ? 'selected' : '' }}>Uzbekistan</option>
                                <option value="VU" {{ old('country', $proxy->country) == 'VU' ? 'selected' : '' }}>Vanuatu</option>
                                <option value="VE" {{ old('country', $proxy->country) == 'VE' ? 'selected' : '' }}>Venezuela</option>
                                <option value="VN" {{ old('country', $proxy->country) == 'VN' ? 'selected' : '' }}>Vietnam</option>
                                <option value="VG" {{ old('country', $proxy->country) == 'VG' ? 'selected' : '' }}>Virgin Islands, British</option>
                                <option value="VI" {{ old('country', $proxy->country) == 'VI' ? 'selected' : '' }}>Virgin Islands, U.S.</option>
                                <option value="WF" {{ old('country', $proxy->country) == 'WF' ? 'selected' : '' }}>Wallis and Futuna</option>
                                <option value="EH" {{ old('country', $proxy->country) == 'EH' ? 'selected' : '' }}>Western Sahara</option>
                                <option value="YE" {{ old('country', $proxy->country) == 'YE' ? 'selected' : '' }}>Yemen</option>
                                <option value="ZM" {{ old('country', $proxy->country) == 'ZM' ? 'selected' : '' }}>Zambia</option>
                                <option value="ZW" {{ old('country', $proxy->country) == 'ZW' ? 'selected' : '' }}>Zimbabwe</option>
                            </select>
                            @error('country')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="expiring_at">Expiring at</label>
                            <input type="datetime-local" name="expiring_at" id="expiring_at" class="form-control @error('expiring_at') is-invalid @enderror" value="{{ old('expiring_at', $proxy->expiring_at) }}">
                            @error('expiring_at')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="submit" name="save" class="btn btn-primary">Save & Continue</button>
                        <a href="{{ route('admin.proxies.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
