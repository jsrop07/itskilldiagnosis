# <div align="center">IT스킬진단 테스트(코딩테스트 문제은행)</div>

## IT스킬 진단 화면

<table>
  <tr>
    <td align="center" width="50%">
      <figure>
        <figcaption>📌 IT스킬 진단 신청 화면(지원자)</figcaption>
        <img src="https://github.com/user-attachments/assets/92f45d1f-ff1a-4486-ba09-22e4ebd2d020" width="100%" />
      </figure>
    </td>
    <td align="center" width="50%">
      <figure>
        <figcaption>📌 IT스킬 진단 신청 및 현황 화면(관리자) </figcaption>
        <img src="https://github.com/user-attachments/assets/a739d61d-b124-4110-89e9-97cb1b7f9ce2" width="100%" />
      </figure>
    </td>
  </tr>
</table>

---

## 프로젝트 개요
**프로젝트명:** ITスキル診断システム(IT스킬진단시스템)
- **프로젝트 기간:** 2024.04 ~ 2024.07 (초기 개발)  / 2024.08 ~ 2025.04 (간헐적 기능 추가 및 유지보수)
- **프로젝트 형태:** 채용 전형용 IT 스킬 진단(코딩 테스트) 웹 애플리케이션
- **목표:** 지원자의 IT 역량(기초 지식·코딩 능력)을 온라인으로 진단하고, 진단 결과를 채용 평가에 활용할 수 있도록 하는 것
- **주요 타겟 사용자:** IT 직군 채용 지원자, 인사/채용 담당자, 기술 면접관 및 관련 부서 관리자

---

## 프로젝트 소개

### 프로젝트 배경
회사에 입사 지원한 지원자들의 기초적인 코딩 역량을 객관적으로 평가하기 위한 기존 프로세스는 다음과 같은 문제점이 있었습니다:

**비표준화된 코딩 역량 평가:**

- 서류 및 면접 위주의 평가로 실제 코딩 능력을 정량적으로 확인하기 어려움  
- 면접관 개인 경험에 의존한 질문으로 인해 평가 기준이 일관되지 않음  
- 동일 직무 지원자 간 역량 비교가 체계적으로 이루어지지 않음  

**외부/수동 프로세스 의존:**

- 외부 코딩 테스트 플랫폼 또는 이메일·문서 형태의 과제 전송 등으로 평가 절차가 분산되어 관리가 어려움  
- 테스트 링크 발송, 답안 취합, 채점 결과 정리 등을 담당자가 수동으로 처리해야 해 업무 부담이 큼  
- 회사에 맞는 맞춤형 문제 구성 및 난이도 조정에 한계가 있음  

**평가 결과 관리 및 활용의 비효율성:**

- 지원자별 테스트 결과가 표준화된 형식으로 축적되지 않아, 이후 전형(면접, 최종 평가)에서 활용하기 어려움  
- 합격/불합격 여부 외에 세부 역량(알고리즘, 기본 문법 이해도, 문제 해결력 등)에 대한 인사이트 도출이 제한적임  
- 반복 채용 시마다 동일한 과정을 처음부터 다시 세팅해야 하는 비효율 발생  

**ITスキル診断システム(IT 스킬 진단 시스템)** 은 위와 같은 문제를 해결하기 위해,  
지원자의 코딩 역량을 온라인으로 표준화하여 진단하고, 결과를 구조화된 형태로 저장·분석할 수 있는 **자체 코딩 테스트 플랫폼**으로 설계되었습니다. 
이를 통해 채용 과정에서 지원자의 기초·실무 코딩 능력을 공정하고 일관되게 평가하고, 인사·기술 면접 단계에서 활용 가능한 정량적 데이터를 제공하는 것을 목표로 합니다.

---

## 프로젝트 목표

- 지원자의 논리적 문제해결·기초·알고리즘 코딩 역량을 온라인으로 표준화하여 정량 평가할 수 있는 사내 코딩 테스트 시스템 구축  
- 코딩 테스트 운영(진단 응시 신청, 문제 제공, 응시, 제출, 채점) 프로세스를 자동화하여 채용 담당자의 업무 부담 감소  
- 지원자별 평가 결과를 구조화하여, 이후 면접 및 채용 의사결정에 활용 가능한 데이터 제공

---

## 주요 기능

### 1. 응시자 화면

#### 1-1. 진단 신청 화면(`/applicant/application`)
- **필수입력(유효성 검사) :** 메일 | 이름(전각) | 이름(가타카나) | 생년월일 | 응모구분 | 학력 | IT스킬유무 | 경험분야 | 문제종별 | IT경력
- **그외입력 :** 성별 | 전공 | IT관련자격증 | 기타

<div align="center">
  <img src="https://github.com/user-attachments/assets/111fd7a3-d332-4d37-b2fd-943278f41846" alt="application" width="800px" />
</div>



#### 1-2. 응시(테스트) 화면
- 메일로 안내된 아이디 | 비밀번호 입력

<div align="center">
  <img src="https://github.com/user-attachments/assets/2d653ef7-682d-447c-bb9a-bce4690a3831" alt="application" width="800px" />
</div>

- 안내된 시간 내에서 논리적 해결 문제 및 코딩 문제 풀이
- 남은 시험 시간, 현재 진행 중인 문항 번호 등 응시 상태 표시
<div align="center">
  <img src="https://github.com/user-attachments/assets/786aee2b-a678-4030-87a6-536a6d34cdcc" alt="application" width="800px" />
</div>

- 제출 후, 응시 완료 처리 및 결과 안내
  <table>
  <tr>
    <td align="center" width="50%">
          <img src="https://github.com/user-attachments/assets/1959476b-b76a-419b-9635-4ecb45c2a42c"  alt="adminlogin" width="800" />
    </td>
    <td align="center" width="50%">
        <img src="https://github.com/user-attachments/assets/9a9345d2-dfb4-4d19-9f78-83cb272ef561" width="100%" />
    </td>
  </tr>
</table>


### 2. 관리자 화면

#### 2-1. 관리자 로그인
- 이메일 | 비밀번호
- 로그인시 응시자 신청일람으로 이동

   <div align="center">
       <img src="https://github.com/user-attachments/assets/06c024c8-f394-44cf-b5e8-4800003f2a02" alt="adminlogin" width="800px" />
   </div>
  
#### 2-1. 응시자 신청 일람 및 진단 관리
- 진단 신청자 목록 조회 (상태별 필터/검색, 날짜, 페이징)
- 응시 상태(신규, 완료, 실격) 및 진단 결과 요약 확인
- 개별 지원자의 응시 이력 및 상세 결과 조회 및 신규등록, 진단 의뢰 메일 송신 및 결과 메일 송신
  
<div align="center">
      <img src="https://github.com/user-attachments/assets/2cf93ef9-d1aa-4c64-98cb-2270931033fc"
       alt="adminlogin" width="800" />
      <img src="https://github.com/user-attachments/assets/9a18201c-0dcf-456b-99df-66ce097e975e"
       alt="screen" width="800" />
  <img src="https://github.com/user-attachments/assets/a983e0c0-28a4-4996-a380-dd8f5687be25"
       alt="adminlogin" width="800" />
  <img src="https://github.com/user-attachments/assets/5078b03d-ee8a-45bb-8f3d-7bd83eb88502"
       alt="screen" width="800" />
</div>

---
  
#### 2-2. 문제집 관리 화면
- 직무/레벨(신입/경력)별 문제집 목록 조회
- 문제집 생성, 수정, 삭제 등 관리 기능

<div align="center">
      <img src="https://github.com/user-attachments/assets/e4bb61f6-2e3d-45a3-b61b-6210e775fb85" alt="adminlogin" width="800" />
</div>

---

#### 2-3. 문제(문항) 관리 화면
- 문제집에 포함될 개별 문제 일람 조회
 <div align="center">
      <img src="https://github.com/user-attachments/assets/ea48daa1-f32b-499e-bd51-9c90784a90e6" alt="adminlogin" width="800" />
</div>

- 문제 등록/수정/삭제 기능 (문제 내용, 난이도, 배점, 정답 등 설정)
- 문제 등록시 언어설정 가능(일본어, 한국어)
  <table>
  <tr>
    <td align="center" width="50%">
          <img src="https://github.com/user-attachments/assets/9d47ac7b-6f6e-41bc-b9a1-d9fdcc8d176b"  alt="adminlogin" width="800" />
    </td>
    <td align="center" width="50%">
        <img src="https://github.com/user-attachments/assets/1cebe292-ed79-4384-810c-8d5e3aa329d9" width="100%" />
    </td>
  </tr>
</table>

- CSV파일을 이용하여 문제 등록 가능
<div align="center">
      <img src="https://github.com/user-attachments/assets/3f5c1852-e5b8-4b7a-9258-8c8ab2e2e78e" alt="adminlogin" width="800" />
</div>

---

## 팀원 소개
| 이름   | 역할           | 담당 부분|
|--------|----------------|------------------------------------------------|
| 정석원 | FE/BE          |  진단 신청 화면, 응시화면 및 관리자화면의 신청목록화면  |
| 박하성 | FE/BE          | 관리자 문제집화면 및 문제 일람 화면|

---

## 기술 스택

| 분류        | 기술 스택 |
|-------------|-----------|
| 프론트엔드  | ![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white) ![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white) ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black) ![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white) |
| 백엔드      | ![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![Zend Framework](https://img.shields.io/badge/Zend%20Framework-68B604?style=for-the-badge&logoColor=white) |
| 데이터베이스 | ![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white) |
| 인프라      | ![AWS](https://img.shields.io/badge/Amazon%20AWS-232F3E?style=for-the-badge&logo=amazonaws&logoColor=white) |



