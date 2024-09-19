import cv2
import pyttsx3


# Load pre-trained Haar cascades for face and eye detection
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
eye_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_eye.xml')

# Initialize the text-to-speech engine
engine = pyttsx3.init()


def speak_warning(message):
    """Function to speak out the warning message."""
    engine.say(message)
    engine.runAndWait()


def eyes_open(frame_gray, face_region):
    """Check if eyes are detected within the face region."""
    (x, y, w, h) = face_region
    roi_gray = frame_gray[y:y + h, x:x + w]
    eyes = eye_cascade.detectMultiScale(roi_gray, scaleFactor=1.3, minNeighbors=5)
    return len(eyes) > 0


def main():
    # Open a connection to the webcam
    video_capture = cv2.VideoCapture(0)

    while True:
        # Capture frame-by-frame
        ret, frame = video_capture.read()
        if not ret:
            print("Failed to grab frame")
            break

        # Convert the frame to grayscale for face and eye detection
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)

        # Detect faces in the frame
        faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(30, 30))

        if len(faces) == 0:
            print("No face detected")
        else:
            for (x, y, w, h) in faces:
                # Draw a rectangle around the face
                cv2.rectangle(frame, (x, y), (x + w, y + h), (255, 0, 0), 2)

                # Check if eyes are detected within the face region
                if not eyes_open(gray, (x, y, w, h)):
                    # No eyes detected (eyes are closed)
                    cv2.putText(frame, "Warning: Eyes Closed!", (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255),
                                2)
                    speak_warning("Don't sleep!")  # Speak warning message

        # Display the resulting frame
        cv2.imshow('Video', frame)

        # Break the loop when 'q' is pressed
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    # Release the capture and close any open windows
    video_capture.release()
    cv2.destroyAllWindows()


if __name__ == "__main__":
    main()
